<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Porudžbine svih kupaca, za menadžera. Rute su iza can:orders.manage,
 * pa ovde nema provere dozvole (odeljak 12.4).
 */
class OrderController extends Controller
{
    /**
     * Sve porudžbine.
     *
     * Sa podacima o kupcu; filter status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
        ]);

        $orders = Order::query()
            ->with('user')
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->latest('id')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Promena statusa porudžbine.
     *
     * Dozvoljeni prelazi su pending u paid i paid u shipped; ostali daju
     * 409.
     *
     * @throws InvalidStatusTransitionException
     */
    public function update(Request $request, Order $order): OrderResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)
                ->only([OrderStatus::Paid, OrderStatus::Shipped])],
        ]);
        $next = OrderStatus::from($data['status']);

        DB::transaction(function () use (&$order, $next) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (! $order->status->canBecome($next)) {
                throw new InvalidStatusTransitionException($order, $next);
            }
            $order->changeStatus($next);
        });

        return new OrderResource($order->load('user', 'items.book'));
    }
}
