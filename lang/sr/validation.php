<?php

/*
 * Poruke validacije na srpskom (latinica). Prevedena su pravila koja
 * knjižara koristi i najčešća ostala; za ostala važi engleski
 * (fallback_locale), dok se ne dodaju ovde.
 */

return [

    'accepted' => 'Polje :attribute mora biti prihvaćeno.',
    'after' => 'Polje :attribute mora biti datum posle :date.',
    'after_or_equal' => 'Polje :attribute mora biti datum :date ili posle njega.',
    'array' => 'Polje :attribute mora biti niz.',
    'before' => 'Polje :attribute mora biti datum pre :date.',
    'between' => [
        'array' => 'Polje :attribute mora imati između :min i :max stavki.',
        'file' => 'Fajl :attribute mora imati između :min i :max kilobajta.',
        'numeric' => 'Polje :attribute mora biti između :min i :max.',
        'string' => 'Polje :attribute mora imati između :min i :max znakova.',
    ],
    'boolean' => 'Polje :attribute mora biti tačno ili netačno.',
    'confirmed' => 'Potvrda polja :attribute se ne poklapa.',
    'date' => 'Polje :attribute mora biti ispravan datum.',
    'decimal' => 'Polje :attribute mora imati :decimal decimala.',
    'different' => 'Polja :attribute i :other moraju biti različita.',
    'digits' => 'Polje :attribute mora imati tačno :digits cifara.',
    'digits_between' => 'Polje :attribute mora imati između :min i :max cifara.',
    'distinct' => 'Polje :attribute ima vrednost koja se ponavlja.',
    'email' => 'Polje :attribute mora biti ispravna email adresa.',
    'enum' => 'Izabrana vrednost polja :attribute nije dozvoljena.',
    'exists' => 'Izabrana vrednost polja :attribute ne postoji.',
    'file' => 'Polje :attribute mora biti fajl.',
    'filled' => 'Polje :attribute ne sme biti prazno.',
    'gt' => [
        'numeric' => 'Polje :attribute mora biti veće od :value.',
        'string' => 'Polje :attribute mora imati više od :value znakova.',
    ],
    'gte' => [
        'numeric' => 'Polje :attribute mora biti najmanje :value.',
        'string' => 'Polje :attribute mora imati najmanje :value znakova.',
    ],
    'image' => 'Polje :attribute mora biti slika.',
    'in' => 'Izabrana vrednost polja :attribute nije dozvoljena.',
    'integer' => 'Polje :attribute mora biti ceo broj.',
    'json' => 'Polje :attribute mora biti ispravan JSON.',
    'lt' => [
        'numeric' => 'Polje :attribute mora biti manje od :value.',
    ],
    'lte' => [
        'numeric' => 'Polje :attribute može biti najviše :value.',
    ],
    'max' => [
        'array' => 'Polje :attribute može imati najviše :max stavki.',
        'file' => 'Fajl :attribute može imati najviše :max kilobajta.',
        'numeric' => 'Polje :attribute može biti najviše :max.',
        'string' => 'Polje :attribute može imati najviše :max znakova.',
    ],
    'mimes' => 'Polje :attribute mora biti fajl tipa: :values.',
    'min' => [
        'array' => 'Polje :attribute mora imati najmanje :min stavki.',
        'file' => 'Fajl :attribute mora imati najmanje :min kilobajta.',
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'string' => 'Polje :attribute mora imati najmanje :min znakova.',
    ],
    'not_in' => 'Izabrana vrednost polja :attribute nije dozvoljena.',
    'numeric' => 'Polje :attribute mora biti broj.',
    'present' => 'Polje :attribute mora postojati.',
    'prohibited' => 'Polje :attribute nije dozvoljeno.',
    'regex' => 'Polje :attribute nije u ispravnom obliku.',
    'required' => 'Polje :attribute je obavezno.',
    'required_if' => 'Polje :attribute je obavezno kada je :other :value.',
    'required_with' => 'Polje :attribute je obavezno kada postoji :values.',
    'same' => 'Polja :attribute i :other moraju biti ista.',
    'size' => [
        'array' => 'Polje :attribute mora imati :size stavki.',
        'numeric' => 'Polje :attribute mora biti :size.',
        'string' => 'Polje :attribute mora imati :size znakova.',
    ],
    'string' => 'Polje :attribute mora biti tekst.',
    'unique' => 'Vrednost polja :attribute je već zauzeta.',
    'url' => 'Polje :attribute mora biti ispravna adresa.',
    'uuid' => 'Polje :attribute mora biti ispravan UUID.',

    // Sopstvena pravila knjižare (app/Rules).
    'isbn13' => 'Polje :attribute mora imati tačno 13 cifara.',
    'isbn13_checksum' => 'Polje :attribute nije ispravan ISBN: kontrolna cifra se ne slaže.',

    /*
     * Poruke za pojedinačna polja, kada opšta poruka nije dovoljno jasna.
     */
    'custom' => [
        'published_year' => [
            'between' => 'Godina izdanja mora biti između :min i :max.',
        ],
        'author_ids' => [
            'required' => 'Knjiga mora imati bar jednog autora.',
            'min' => 'Knjiga mora imati bar jednog autora.',
        ],
    ],

    /*
     * Imena polja u porukama: "Polje cena je obavezno.", a ne "price".
     */
    'attributes' => [
        'title' => 'naslov',
        'isbn' => 'ISBN',
        'description' => 'opis',
        'price' => 'cena',
        'stock' => 'stanje',
        'published_year' => 'godina izdanja',
        'category_id' => 'kategorija',
        'author_ids' => 'autori',
        'author_ids.*' => 'autor',
        'q' => 'tekst pretrage',
        'category' => 'kategorija',
        'author' => 'autor',
        'min_price' => 'najniža cena',
        'max_price' => 'najviša cena',
        'in_stock' => 'na stanju',
        'page' => 'strana',
        'per_page' => 'broj knjiga po strani',
    ],

];
