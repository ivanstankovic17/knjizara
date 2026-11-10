<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Queries\BookQuery;
use App\Http\Resources\BookResource;
use App\Models\Author;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class AuthorBookController extends Controller
{
    /**
     * Knjige jednog autora.
     *
     * Isti parametri kao spisak svih knjiga.
     *
     * @throws ValidationException
     */
    public function __invoke(
        Author $author,
        BookQuery $query,
    ): AnonymousResourceCollection {
        return BookResource::collection($query->paginate($author->books()));
    }
}
