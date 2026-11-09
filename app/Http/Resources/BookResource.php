<?php

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;

/** @mixin Book */
class BookResource extends ApiResource
{
    /**
     * Reprezentacija knjige iz specifikacije API-ja (docs/api-v1.md).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $this->viewer($request);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'price' => $this->price,
            'in_stock' => $this->stock > 0,
            // Tačno stanje je poslovni podatak: vidi ga samo menadžer.
            'stock' => $this->when((bool) $viewer?->can('update', $this->resource),
                $this->stock),
            'published_year' => $this->published_year,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            // Šta prijavljeni korisnik sme sa ovom knjigom (odeljak 12.6).
            'can' => $this->when($viewer !== null, fn () => [
                'update' => $viewer->can('update', $this->resource),
                'delete' => $viewer->can('delete', $this->resource),
            ]),
        ];
    }
}
