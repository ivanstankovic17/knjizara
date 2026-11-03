<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorController extends Controller
{
    /**
     * GET /api/v1/authors: stranica autora, po imenu, sa brojem knjiga.
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
     * GET /api/v1/authors/{author}
     */
    public function show(Author $author): AuthorResource
    {
        return new AuthorResource($author->loadCount('books'));
    }
}
