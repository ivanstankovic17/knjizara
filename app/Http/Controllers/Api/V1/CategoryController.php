<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * GET /api/v1/categories: sve kategorije, bez paginacije.
     */
    public function index(): JsonResponse
    {
        // Kategorije se retko menjaju, a traže se na svakoj stranici
        // kataloga; keš se briše kada se promene (modeli Book i Category).
        // U keš ide gotova reprezentacija, običan niz, a ne modeli.
        $data = Cache::remember(Category::CACHE_KEY, now()->addHour(),
            fn () => CategoryResource::collection(
                Category::withCount('books')->orderBy('name')->get())->resolve());

        return response()->json(['data' => $data], 200, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v1/categories/{category:slug}
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('books'));
    }
}
