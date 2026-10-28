<?php

use App\Services\PriceFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/status', function (PriceFormatter $prices) {
    return [
        'app' => config('app.name'),
        'laravel' => app()->version(),
        'environment' => app()->environment(),
        'time' => now()->toIso8601String(),
        'example_price' => $prices->format(1290),
    ];
});
