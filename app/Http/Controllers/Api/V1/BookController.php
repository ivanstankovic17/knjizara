<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Queries\BookQuery;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * GET /api/v1/books: stranica knjiga sa pretragom, filterima i sortiranjem.
     */
    public function index(BookQuery $query): AnonymousResourceCollection
    {
        return BookResource::collection($query->paginate(Book::query()));
    }

    /**
     * GET /api/v1/books/{book}: jedna knjiga, uvek sa kategorijom i autorima.
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($book->load('category', 'authors'));
    }
}
