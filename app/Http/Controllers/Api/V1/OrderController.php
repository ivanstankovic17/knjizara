<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelOrder;
use App\Actions\PlaceOrder;
use App\Exceptions\OrderNotCancellableException;
use App\Exceptions\OutOfStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Dedoc\Scramble\Attributes\IgnoreResponse;
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
     * Moje porudžbine.
     *
     * Samo porudžbine prijavljenog kupca, najnovije prve.
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
     * Nova porudžbina.
     *
     * Cene, ukupan iznos i status određuje server. Ako neke knjige nema
     * dovoljno, ništa se ne menja (409).
     *
     * @throws OutOfStockException
     */
    public function store(
        StoreOrderRequest $request,
        PlaceOrder $placeOrder,
    ): JsonResponse {
        $order = $placeOrder->handle($request->user(), $request->cart());

        return (new OrderResource($order->load('items.book')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.orders.show', $order->id));
    }

    /**
     * Jedna porudžbina.
     *
     * Vlasnik ili menadžer; ostali dobijaju 404, kao da porudžbina ne postoji.
     */
    #[IgnoreResponse(403)] // OrderPolicy odbija sa 404 (denyAsNotFound), ne sa 403.
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        // Kupac se učitava uvek, a OrderResource ga prikazuje samo menadžeru.
        return new OrderResource($order->load('user', 'items.book'));
    }

    /**
     * Otkazivanje porudžbine.
     *
     * Samo porudžbina na čekanju (pending); inače 409.
     *
     * @throws OrderNotCancellableException
     */
    #[IgnoreResponse(403)] // Kao i show: tuđa porudžbina je 404.
    public function cancel(Order $order, CancelOrder $cancelOrder): OrderResource
    {
        Gate::authorize('cancel', $order);

        return new OrderResource($cancelOrder->handle($order)->load('user', 'items.book'));
    }
}
