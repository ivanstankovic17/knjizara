<?php

use Illuminate\Support\Facades\Artisan;

/*
 * Ugovor iz glave 6 (docs/api-v1.md) i specifikacija koju Scramble
 * generiše iz koda moraju da opisuju isti API (odeljak 15.3).
 */

// Generisanje specifikacije traje nekoliko sekundi, pa ovi testovi imaju
// svoju grupu: pest --exclude-group=dokumentacija ih preskače.
pest()->group('dokumentacija');

/** 400 i 415 za zahtev sa telom (RequireJsonBody), 429 za svaki zahtev. */
const ZAJEDNICKI = ['400', '415', '429'];

/**
 * Redovi tabela iz ugovora: "GET /books/{}" => ['200', '422'].
 *
 * @return array<string, list<string>>
 */
function ugovor(): array
{
    $md = file_get_contents(base_path('docs/api-v1.md'));
    preg_match_all(
        '/^\| `(GET|POST|PATCH|DELETE) (\S+)` \|[^|]*\|([^|]*)\|([^|]*)\|$/m',
        $md, $redovi, PREG_SET_ORDER);

    $ugovor = [];
    foreach ($redovi as [, $metoda, $putanja, $uspeh, $greske]) {
        preg_match_all('/`(\d{3})`/', $uspeh.$greske, $kodovi);
        $kljuc = $metoda.' '.preg_replace('/\{\w+\}/', '{}', $putanja);
        $ugovor[$kljuc] = $kodovi[1];
    }

    return $ugovor;
}

/**
 * Operacije iz specifikacije, u istom obliku kao ugovor().
 *
 * @return array<string, list<string>>
 */
function specifikacija(): array
{
    // Generisanje traje nekoliko sekundi, pa se radi jednom za ceo fajl.
    static $operacije = null;
    if ($operacije !== null) {
        return $operacije;
    }

    Artisan::call('scramble:export', ['--stdout' => true]);
    $spec = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    $operacije = [];
    foreach ($spec['paths'] as $putanja => $metode) {
        foreach (['get', 'post', 'patch', 'delete'] as $metoda) {
            if (isset($metode[$metoda])) {
                $kljuc = strtoupper($metoda).' '
                    .preg_replace('/\{\w+\}/', '{}', $putanja);
                $operacije[$kljuc] = array_map('strval',
                    array_keys($metode[$metoda]['responses']));
            }
        }
    }

    return $operacije;
}

it('dokumentuje tačno one operacije koje su u ugovoru', function () {
    expect(array_keys(specifikacija()))
        ->toEqualCanonicalizing(array_keys(ugovor()));
});

it('dokumentuje iste statusne kodove kao ugovor', function () {
    $spec = specifikacija();
    $razlike = [];

    foreach (ugovor() as $operacija => $kodovi) {
        // Kodove zajedničke za sve zahteve ugovor navodi jednom, ispod
        // tabele; 429 proverava poslednji test u ovom fajlu.
        $dokumentovani = array_diff($spec[$operacija] ?? [], ZAJEDNICKI);
        $kodovi = array_diff($kodovi, ZAJEDNICKI);
        $nedostaju = array_diff($kodovi, $dokumentovani);
        $visak = array_diff($dokumentovani, $kodovi);

        if ($nedostaju || $visak) {
            $razlike[$operacija] = trim(
                ($nedostaju ? 'nedostaje '.implode(', ', $nedostaju).' ' : '')
                .($visak ? 'višak '.implode(', ', $visak) : ''));
        }
    }

    expect($razlike)->toBe([]);
});

it('za svaki zahtev dokumentuje 429', function () {
    $bez429 = array_keys(array_filter(specifikacija(),
        fn (array $kodovi) => ! in_array('429', $kodovi, true)));

    expect($bez429)->toBe([]);
});
