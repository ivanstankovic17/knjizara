<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Queries\BookQuery;
use App\Http\Resources\BookResource;
use App\Models\Author;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorBookController extends Controller
{
    /**
     * GET /api/v1/authors/{author}/books: knjige jednog autora, sa istim
     * parametrima kao spisak svih knjiga.
     */
    public function __invoke(
        Author $author,
        BookQuery $query,
    ): AnonymousResourceCollection {
        return BookResource::collection($query->paginate($author->books()));
    }
}
