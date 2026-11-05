<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelOrder;
use App\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Porudžbine prijavljenog kupca. Svaki upit ide preko $user->orders(),
 * pa tuđa porudžbina za kupca jednostavno ne postoji (404, odeljak 6.4).
 */
class OrderController extends Controller
{
    /**
     * GET /api/v1/orders: sopstvene porudžbine, najnovije prve.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->latest()
            ->latest('id')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/v1/orders: 201, 409 (nema na stanju), 422.
     */
    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): JsonResponse
    {
        $order = $placeOrder->handle($request->user(), $request->cart());

        return (new OrderResource($order->load('items.book')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.orders.show', $order->id));
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->find($request, $order)->load('items.book'));
    }

    /**
     * POST /api/v1/orders/{id}/cancel: 200 ili 409.
     */
    public function cancel(Request $request, int $order, CancelOrder $cancelOrder): OrderResource
    {
        $cancelled = $cancelOrder->handle($this->find($request, $order));

        return new OrderResource($cancelled->load('items.book'));
    }

    private function find(Request $request, int $id): Order
    {
        return $request->user()->orders()->findOrFail($id);
    }
}
