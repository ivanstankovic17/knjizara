<?php

namespace App\Http\Controllers;

use App\Services\PriceFormatter;

class StatusController extends Controller
{
    /**
     * Osnovni podaci o aplikaciji; služi za proveru da li API radi.
     */
    public function __invoke(PriceFormatter $prices): array
    {
        return [
            'app' => config('app.name'),
            'laravel' => app()->version(),
            'environment' => app()->environment(),
            'locale' => app()->getLocale(),
            'time' => now()->toIso8601String(),
            'example_price' => $prices->format(1290),
        ];
    }
}
