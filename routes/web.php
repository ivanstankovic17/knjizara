<?php

use App\Http\Controllers\GithubLoginController;
use App\Http\Controllers\HomeController;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Stranice JavaScript klijenta; podatke dobijaju od API-ja (glava 9).
Route::view('/katalog', 'catalog')->name('catalog');

// Stranice za upravljanje (glava 12): neprijavljen korisnik ide na
// /prijava, a prijavljen bez dozvole dobija 403.
Route::view('/katalog/nova-knjiga', 'new-book')
    ->middleware(['auth', 'can:create,App\Models\Book'])->name('books.create');
Route::view('/upravljanje/porudzbine', 'admin-orders')
    ->middleware(['auth', 'can:orders.manage'])->name('admin.orders');

// Nalog (glava 11). Ime "login" traži Laravelov middleware "auth".
Route::view('/prijava', 'login')->name('login');
Route::view('/lozinka/nova', 'reset-password')->name('password.reset');
Route::view('/nalog', 'account')->middleware('auth')->name('account');

// Link iz mejla za potvrdu adrese: potpisan, važi 60 minuta.
Route::get('/email/potvrda/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('account', ['potvrdjeno' => 1]);
})->middleware(['auth', 'signed'])->name('verification.verify');

// Prijava preko GitHub-a (odeljak 11.6).
Route::get('/auth/github', [GithubLoginController::class, 'redirect'])->name('github.redirect');
Route::get('/auth/github/callback', [GithubLoginController::class, 'callback'])
    ->name('github.callback');

// Dokumentacija API-ja (glava 15): Swagger UI nad /docs/api.json.
Route::view('/docs/api', 'api-docs')
    ->middleware(RestrictedDocsAccess::class)
    ->name('docs.api');
