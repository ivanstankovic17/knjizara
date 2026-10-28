<?php

use App\Services\PriceFormatter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('knjizara:cena {iznos}', function (PriceFormatter $prices, int $iznos) {
    $this->info($prices->format($iznos));
})->purpose('Prikazuje iznos u formatu knjižare');
