<?php

namespace Tests\Unit;

use App\Services\PriceFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Isti stil kao Laravelov kostur: klasa, metode test_*, $this->assert*.
 * Pest pokreće i ovakve testove (odeljak 14.2).
 */
class PriceFormatterTest extends TestCase
{
    public function test_dodaje_valutu_iza_iznosa(): void
    {
        $formatter = new PriceFormatter('RSD');

        $this->assertSame('990 RSD', $formatter->format(990));
    }

    #[DataProvider('iznosi')]
    public function test_razdvaja_hiljade_tackom(
        int $amount, string $expected): void
    {
        $this->assertSame($expected, (new PriceFormatter('RSD'))->format($amount));
    }

    public static function iznosi(): array
    {
        return [
            'nula' => [0, '0 RSD'],
            'hiljada' => [1000, '1.000 RSD'],
            'milion' => [1234567, '1.234.567 RSD'],
        ];
    }
}
