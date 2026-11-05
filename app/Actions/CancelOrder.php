<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Otkazuje porudžbinu na čekanju i vraća knjige na stanje.
 */
class CancelOrder
{
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Ponovo pročitano i zaključano: dva istovremena otkazivanja
            // ne smeju dva puta da vrate knjige na stanje.
            $order = Order::lockForUpdate()->with('items.book')->findOrFail($order->id);

            if ($order->status !== OrderStatus::Pending) {
                throw new OrderNotCancellableException($order);
            }

            foreach ($order->items as $item) {
                $item->book->increment('stock', $item->quantity);
            }

            $order->changeStatus(OrderStatus::Cancelled);

            return $order;
        });
    }
}
