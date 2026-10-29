<?php

namespace App\Exceptions;

use App\Models\Book;
use RuntimeException;

class OutOfStockException extends RuntimeException
{
    public function __construct(public readonly Book $book, int $wanted)
    {
        parent::__construct(sprintf(
            'Knjige "%s" ima %d na stanju, traženo je %d.',
            $book->title, $book->stock, $wanted,
        ));
    }
}
