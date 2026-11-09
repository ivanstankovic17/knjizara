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
use Illuminate\Support\Facades\Gate;

/**
 * Porudžbine prijavljenog kupca. Spisak je uvek ograničen na sopstvene
 * porudžbine; pojedinačnu porudžbinu proverava OrderPolicy (glava 12).
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
     * GET /api/v1/orders/{order}: vlasnik ili menadžer; ostali dobijaju 404.
     */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        // Kupac se učitava uvek, a OrderResource ga prikazuje samo menadžeru.
        return new OrderResource($order->load('user', 'items.book'));
    }

    /**
     * POST /api/v1/orders/{order}/cancel: 200 ili 409.
     */
    public function cancel(Order $order, CancelOrder $cancelOrder): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($cancelOrder->handle($order)->load('user', 'items.book'));
    }
}
