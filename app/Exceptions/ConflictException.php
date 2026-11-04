<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Zahtev je ispravan, ali se kosi sa trenutnim stanjem podataka (409).
 * Ovo nisu greške u programu, pa se ne upisuju u log.
 */
abstract class ConflictException extends RuntimeException
{
    /**
     * Poslednji deo URI-ja koji identifikuje vrstu problema (RFC 9457).
     */
    abstract public function type(): string;

    abstract public function title(): string;

    /**
     * Opis za klijenta; podrazumevano isti kao poruka izuzetka.
     */
    public function detail(): string
    {
        return $this->getMessage();
    }
}
