<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\HasBooksException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    /**
     * Spisak autora.
     *
     * Po imenu, sa brojem knjiga.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ])['per_page'] ?? 15;

        $authors = Author::query()
            ->withCount('books')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return AuthorResource::collection($authors);
    }

    /**
     * Jedan autor.
     */
    public function show(Author $author): AuthorResource
    {
        return new AuthorResource($author->loadCount('books'));
    }

    /**
     * Novi autor.
     *
     * Odgovor 201 ima zaglavlje Location sa adresom novog autora.
     */
    public function store(Request $request): JsonResponse
    {
        $author = Author::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ]));

        return (new AuthorResource($author->loadCount('books')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.authors.show', $author));
    }

    /**
     * Izmena autora.
     *
     * Menjaju se samo poslata polja.
     */
    public function update(Request $request, Author $author): AuthorResource
    {
        $author->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]));

        return new AuthorResource($author->loadCount('books'));
    }

    /**
     * Brisanje autora.
     *
     * Autor koji ima knjige ne može da se obriše (409).
     *
     * @throws HasBooksException
     */
    public function destroy(Author $author): Response
    {
        $books = $author->books()->count();
        if ($books > 0) {
            throw new HasBooksException('Autor', $books);
        }

        $author->delete();

        return response()->noContent();
    }
}
