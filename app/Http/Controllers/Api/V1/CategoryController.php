<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * GET /api/v1/categories: sve kategorije, bez paginacije.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::withCount('books')->orderBy('name')->get());
    }

    /**
     * GET /api/v1/categories/{category:slug}
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('books'));
    }
}
