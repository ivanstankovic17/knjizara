<?php

namespace App\Exceptions;

use App\Models\Book;

class OutOfStockException extends ConflictException
{
    public function __construct(public readonly Book $book, int $wanted)
    {
        parent::__construct(sprintf(
            'Knjige "%s" ima %d na stanju, traženo je %d.',
            $book->title, $book->stock, $wanted,
        ));
    }

    public function type(): string
    {
        return 'nema-na-stanju';
    }

    public function title(): string
    {
        return 'Knjige nema dovoljno na stanju';
    }

    /**
     * Kupac ne vidi tačno stanje zaliha (odeljak 6.2), pa ga poruka
     * za klijenta ne sadrži; poruka izuzetka sa brojem ostaje za log.
     */
    public function detail(): string
    {
        return sprintf('Knjige „%s” trenutno nema u traženoj količini.', $this->book->title);
    }
}
