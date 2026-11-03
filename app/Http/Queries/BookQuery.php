<?php

namespace App\Http\Queries;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pretraga, filteri, sortiranje, include i paginacija spiska knjiga,
 * prema parametrima iz specifikacije API-ja (odeljak 6.10).
 */
class BookQuery
{
    public const SORTS = ['title', 'price', 'published_year', 'created_at'];

    public const INCLUDES = ['authors', 'category'];

    private const PARAMS = [
        'q', 'category', 'author', 'min_price', 'max_price', 'in_stock',
        'sort', 'include', 'page', 'per_page',
    ];

    public function __construct(private Request $request) {}

    public function paginate(Builder|Relation $query): LengthAwarePaginator
    {
        $p = $this->validated();

        $query
            ->when($p['q'] ?? null, fn ($q, string $text) => $q->where(
                fn (Builder $w) => $w
                    ->where('title', 'like', "%{$text}%")
                    ->orWhereRelation('authors', 'name', 'like', "%{$text}%")))
            ->when($p['category'] ?? null, fn ($q, string $slug) => $q
                ->whereRelation('category', 'slug', $slug))
            ->when($p['author'] ?? null, fn ($q, string $ids) => $q
                ->whereHas('authors', fn (Builder $a) => $a
                    ->whereIn('authors.id', explode(',', $ids))))
            ->when(isset($p['min_price']), fn ($q) => $q
                ->where('price', '>=', $p['min_price']))
            ->when(isset($p['max_price']), fn ($q) => $q
                ->where('price', '<=', $p['max_price']))
            ->when($p['in_stock'] ?? false, fn ($q) => $q->where('stock', '>', 0));

        foreach ($this->sorts($p['sort'] ?? 'title') as [$column, $direction]) {
            $query->orderBy("books.{$column}", $direction);
        }
        $query->orderBy('books.id');

        if (isset($p['include'])) {
            $query->with(explode(',', $p['include']));
        }

        return $query->paginate($p['per_page'] ?? 15)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validated(): array
    {
        $unknown = array_diff(array_keys($this->request->query()), self::PARAMS);
        if ($unknown) {
            throw ValidationException::withMessages(
                array_fill_keys($unknown, 'Nepoznat parametar.'));
        }

        return Validator::make($this->request->query(), [
            'q' => ['sometimes', 'string', 'min:2', 'max:100'],
            'category' => ['sometimes', 'string', Rule::exists('categories', 'slug')],
            'author' => ['sometimes', 'regex:/^\d+(,\d+)*$/'],
            'min_price' => ['sometimes', 'integer', 'min:0'],
            'max_price' => ['sometimes', 'integer', 'min:0'],
            'in_stock' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', $this->onlyFrom(self::SORTS, true)],
            'include' => ['sometimes', 'string', $this->onlyFrom(self::INCLUDES)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ])->validate();
    }

    /**
     * Pravilo validacije: vrednost je spisak odvojen zarezima, a svaki
     * član je sa spiska dozvoljenih (uz minus ispred, ako je $signed).
     */
    private function onlyFrom(array $allowed, bool $signed = false): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use (
            $allowed, $signed
        ) {
            foreach (explode(',', (string) $value) as $item) {
                if (! in_array($signed ? ltrim($item, '-') : $item, $allowed, true)) {
                    $fail("Vrednost „{$item}” nije dozvoljena. Dozvoljeno: "
                        .implode(', ', $allowed).'.');
                }
            }
        };
    }

    /**
     * "-published_year,title" => [['published_year', 'desc'], ['title', 'asc']]
     *
     * @return list<array{string, string}>
     */
    private function sorts(string $sort): array
    {
        return array_map(
            fn (string $f) => str_starts_with($f, '-') ? [substr($f, 1), 'desc'] : [$f, 'asc'],
            explode(',', $sort),
        );
    }
}
