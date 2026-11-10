<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\OutOfStockException;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceOrder
{
    /**
     * @param  array<int, int>  $cart  ID knjige => količina
     */
    public function handle(User $user, array $cart): Order
    {
        $order = DB::transaction(function () use ($user, $cart) {
            $books = Book::lockForUpdate()
                ->findOrFail(array_keys($cart))
                ->keyBy('id');

            $order = $user->orders()->create([
                'status' => OrderStatus::Pending,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($cart as $bookId => $quantity) {
                $book = $books[$bookId];

                if ($book->stock < $quantity) {
                    throw new OutOfStockException($book, $quantity);
                }

                $book->decrement('stock', $quantity);
                $order->items()->create([
                    'book_id' => $book->id,
                    'quantity' => $quantity,
                    'unit_price' => $book->price,
                ]);
                $total += $book->price * $quantity;
            }

            $order->update(['total' => $total]);

            // Šalje se tek posle potvrde transakcije (ShouldDispatchAfterCommit).
            OrderPlaced::dispatch($order);

            return $order;
        });

        // Poslovni događaj u logu (odeljak 16.7): posle potvrde transakcije,
        // sa podacima po kojima se pretražuje, bez ličnih podataka kupca.
        Log::info('Porudžbina primljena', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'items' => count($cart),
            'total' => $order->total,
        ]);

        return $order;
    }
}
