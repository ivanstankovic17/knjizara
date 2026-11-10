<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\HasBooksException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Sve kategorije.
     *
     * Bez paginacije; odgovor ima ETag.
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
     * Jedna kategorija.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('books'));
    }

    /**
     * Nova kategorija.
     *
     * Slug se pravi od imena i posle se ne menja, jer je deo adrese.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $slug = Str::slug($data['name']);

        if ($slug === '' || Category::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Kategorija sa tim imenom već postoji.',
            ]);
        }

        $category = Category::create(['name' => $data['name'], 'slug' => $slug]);

        return (new CategoryResource($category->loadCount('books')))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.categories.show', $category));
    }

    /**
     * Promena imena kategorije.
     *
     * Slug ostaje isti, pa stare adrese i dalje rade.
     */
    public function update(Request $request, Category $category): CategoryResource
    {
        $category->update(
            $request->validate(['name' => ['required', 'string', 'max:60']]));

        return new CategoryResource($category->loadCount('books'));
    }

    /**
     * Brisanje kategorije.
     *
     * Kategorija koja ima knjige ne može da se obriše (409).
     *
     * @throws HasBooksException
     */
    public function destroy(Category $category): Response
    {
        $books = $category->books()->count();
        if ($books > 0) {
            throw new HasBooksException('Kategorija', $books);
        }

        $category->delete();

        return response()->noContent();
    }
}
