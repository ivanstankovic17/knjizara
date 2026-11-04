<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ISBN-13: tačno 13 cifara, od kojih je poslednja kontrolna.
 * Cifre na neparnim mestima imaju težinu 1, na parnim 3, a zbir
 * svih proizvoda mora biti deljiv sa 10.
 */
class Isbn13 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{13}$/', $value)) {
            $fail('validation.isbn13')->translate();

            return;
        }

        $sum = 0;
        foreach (str_split($value) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        if ($sum % 10 !== 0) {
            $fail('validation.isbn13_checksum')->translate();
        }
    }
}
