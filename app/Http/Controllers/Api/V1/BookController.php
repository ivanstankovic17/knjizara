<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookInOrdersException;
use App\Http\Controllers\Controller;
use App\Http\Queries\BookQuery;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
     * POST /api/v1/books: nova knjiga; 201 sa zaglavljem Location.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = DB::transaction(function () use ($request) {
            $book = Book::create($request->safe()->except('author_ids'));
            $book->authors()->sync(self::positions($request->validated('author_ids')));

            return $book;
        });

        return (new BookResource($book->load('category', 'authors')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.books.show', $book));
    }

    /**
     * GET /api/v1/books/{book}: jedna knjiga, uvek sa kategorijom i autorima.
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($book->load('category', 'authors'));
    }

    /**
     * PATCH /api/v1/books/{book}: menjaju se samo poslata polja.
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        DB::transaction(function () use ($request, $book) {
            $book->update($request->safe()->except('author_ids'));
            if ($request->has('author_ids')) {
                $book->authors()->sync(self::positions($request->validated('author_ids')));
            }
        });

        return new BookResource($book->load('category', 'authors'));
    }

    /**
     * DELETE /api/v1/books/{book}: 204, ili 409 ako je knjiga prodavana.
     */
    public function destroy(Book $book): Response
    {
        $orders = $book->orderItems()->distinct()->count('order_id');
        if ($orders > 0) {
            throw new BookInOrdersException($book, $orders);
        }

        $book->delete();

        return response()->noContent();
    }

    /**
     * [7, 12] => [7 => ['position' => 1], 12 => ['position' => 2]]
     *
     * Ključevi ne moraju biti 0, 1, 2...: i JSON objekat {"3": 7, "5": 12}
     * prolazi pravilo "array", pa se redosled pravi sa array_values.
     *
     * @param  array<array-key, int>  $authorIds
     * @return array<int, array{position: int}>
     */
    private static function positions(array $authorIds): array
    {
        $result = [];
        foreach (array_values($authorIds) as $i => $id) {
            $result[$id] = ['position' => $i + 1];
        }

        return $result;
    }
}
