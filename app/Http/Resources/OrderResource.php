<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;

/** @mixin Order */
class OrderResource extends ApiResource
{
    /**
     * Reprezentacija porudžbine iz odeljka 6.10, uz link za otkazivanje
     * dok je porudžbina na čekanju (odeljak 6.7).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total' => $this->total,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'book' => ['id' => $item->book->id, 'title' => $item->book->title],
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])),
            'created_at' => $this->created_at?->toAtomString(),
            'links' => [
                'self' => route('api.v1.orders.show', $this->id),
                'cancel' => $this->when($this->status->value === 'pending',
                    fn () => route('api.v1.orders.cancel', $this->id)),
            ],
        ];
    }
}
