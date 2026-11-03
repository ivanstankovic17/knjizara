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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'price' => $this->price,
            'in_stock' => $this->stock > 0,
            'published_year' => $this->published_year,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
