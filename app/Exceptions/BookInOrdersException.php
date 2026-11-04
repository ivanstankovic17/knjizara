<?php

namespace App\Exceptions;

use App\Models\Book;

class BookInOrdersException extends ConflictException
{
    public function __construct(public readonly Book $book, int $orders)
    {
        parent::__construct(sprintf(
            'Knjiga %d se nalazi u %d %s.',
            $book->id, $orders, self::orders($orders),
        ));
    }

    /**
     * 1 porudžbini, 3 porudžbine, 5 porudžbina, 21 porudžbini, 12 porudžbina.
     */
    private static function orders(int $n): string
    {
        return match (true) {
            $n % 10 === 1 && $n % 100 !== 11 => 'porudžbini',
            in_array($n % 10, [2, 3, 4]) && ! in_array($n % 100, [12, 13, 14]) => 'porudžbine',
            default => 'porudžbina',
        };
    }

    public function type(): string
    {
        return 'knjiga-u-porudzbinama';
    }

    public function title(): string
    {
        return 'Knjiga ne može da se obriše';
    }
}
