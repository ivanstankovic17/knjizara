<?php

namespace App\Http\Resources;

use App\Models\Author;
use Illuminate\Http\Request;

/** @mixin Author */
class AuthorResource extends ApiResource
{
    /**
     * Uz knjigu se ugrađuju samo id i ime; biografija, broj knjiga i link
     * postoje kada se traže sami autori.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $alone = $request->routeIs('api.v1.authors.*')
            && ! $request->routeIs('api.v1.authors.books');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'bio' => $this->when($alone, $this->bio),
            'books_count' => $this->whenCounted('books'),
            'links' => $this->when($alone, fn () => [
                'books' => route('api.v1.authors.books', $this->resource),
            ]),
        ];
    }
}
