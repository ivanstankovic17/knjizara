<?php

use App\Support\Totp;

// Tajna i vremena iz dodatka B standarda RFC 6238. Standard daje kodove
// od 8 cifara; knjižara koristi poslednjih 6.
const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"

it('računa kod iz RFC 6238 za vreme', function (int $time, string $code) {
    expect(Totp::code(RFC_SECRET, $time))->toBe($code);
})->with([
    '59' => [59, '287082'],
    '1111111109' => [1111111109, '081804'],
    '2000000000' => [2000000000, '279037'],
]);

it('prihvata kod iz susednog intervala, ali ne i starijeg', function () {
    $now = 1_800_000_000;
    $previous = Totp::code(RFC_SECRET, $now - 30);
    $tooOld = Totp::code(RFC_SECRET, $now - 60);

    expect(Totp::verify(RFC_SECRET, $previous, $now))->toBeTrue()
        ->and(Totp::verify(RFC_SECRET, $tooOld, $now))->toBeFalse();
});

it('pravi tajnu od 32 znaka base32', function () {
    expect(Totp::secret())->toMatch('/^[A-Z2-7]{32}$/')
        ->not->toBe(Totp::secret());
});
