<?php

namespace App\Http\Queries;

use App\Rules\CommaList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function __construct(private Request $request) {}

    public function paginate(Builder|Relation $query): LengthAwarePaginator
    {
        $p = $this->validated();

        $query
            ->when($p['q'] ?? null, fn ($q, string $text) => $q->where(
                fn (Builder $w) => $w
                    ->where('books.title', 'like', "%{$text}%")
                    ->orWhereIn('books.id', self::booksByAuthor($text))))
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
        $unknown = array_diff(array_keys($this->request->query()), array_keys(self::rules()));
        if ($unknown) {
            throw ValidationException::withMessages(
                array_fill_keys($unknown, 'Nepoznat parametar.'));
        }

        return Validator::make($this->request->query(), self::rules())->validate();
    }

    /**
     * Pravila su ujedno i spisak dozvoljenih parametara. Komentari iznad
     * pravila su opisi parametara u dokumentaciji API-ja (odeljak 15.3).
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            /** Tekst u naslovu ili u imenu autora. */
            'q' => ['sometimes', 'string', 'min:2', 'max:100'],
            /**
             * Slug kategorije.
             *
             * @example roman
             */
            'category' => ['sometimes', 'string', Rule::exists('categories', 'slug')],
            /**
             * Identifikatori autora odvojeni zarezima; knjiga ima bar jednog.
             *
             * @example 7,12
             */
            'author' => ['sometimes', 'regex:/^\d+(,\d+)*$/'],
            /** Najmanja cena u dinarima, uključujući granicu. */
            'min_price' => ['sometimes', 'integer', 'min:0'],
            /** Najveća cena u dinarima, uključujući granicu. */
            'max_price' => ['sometimes', 'integer', 'min:0'],
            /** Samo knjige na stanju. */
            'in_stock' => ['sometimes', 'boolean'],
            /**
             * Polja title, price, published_year, created_at, odvojena zarezima;
             * minus ispred polja znači opadajući redosled. Podrazumevano title.
             *
             * @example -price,title
             */
            'sort' => ['sometimes', 'string',
                new CommaList(self::SORTS, signed: true)],
            /** Povezani resursi koji se ugrađuju u knjigu: authors, category. */
            'include' => ['sometimes', 'string', new CommaList(self::INCLUDES)],
            /** Broj strane, od 1. */
            'page' => ['sometimes', 'integer', 'min:1'],
            /** Broj knjiga na strani; podrazumevano 15. */
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ];
    }

    /**
     * Knjige autora čije ime sadrži tekst, kao podupit za whereIn.
     *
     * orWhereRelation bi napravio exists (...) koji MySQL izvršava za svaku
     * knjigu posebno; sa 50.000 knjiga brojanje rezultata trajalo je 113 ms.
     * Ovaj podupit se izvršava jednom: 10,6 ms (odeljak 17.3).
     */
    private static function booksByAuthor(string $text): QueryBuilder
    {
        return DB::table('author_book')
            ->join('authors', 'authors.id', '=', 'author_book.author_id')
            ->where('authors.name', 'like', "%{$text}%")
            ->select('author_book.book_id');
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
