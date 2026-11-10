<?php

namespace App\Exceptions;

/**
 * Autor ili kategorija sa knjigama ne briše se (409): knjige bi ostale
 * bez autora ili kategorije (odeljak 17.3).
 */
class HasBooksException extends ConflictException
{
    /**
     * @param  string  $what  „Autor” ili „Kategorija”
     */
    public function __construct(private string $what, int $books)
    {
        parent::__construct(sprintf('%s ima %d %s. Prvo premestite ili obrišite knjige.',
            $what, $books, self::books($books)));
    }

    /**
     * 1 knjigu, 3 knjige, 5 knjiga, 21 knjigu, 12 knjiga.
     */
    private static function books(int $n): string
    {
        return match (true) {
            $n % 10 === 1 && $n % 100 !== 11 => 'knjigu',
            in_array($n % 10, [2, 3, 4]) && ! in_array($n % 100, [12, 13, 14]) => 'knjige',
            default => 'knjiga',
        };
    }

    public function type(): string
    {
        return 'ima-knjige';
    }

    public function title(): string
    {
        return "{$this->what} ne može da se obriše";
    }
}
