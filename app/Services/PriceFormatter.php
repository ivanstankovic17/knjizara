<?php

namespace App\Services;

class PriceFormatter
{
    public function __construct(
        private string $currency,
    ) {}

    public function format(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' '.$this->currency;
    }
}
