<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Stranice JavaScript klijenta; podatke dobijaju od API-ja (glava 9).
Route::view('/katalog', 'catalog')->name('catalog');
Route::view('/katalog/nova-knjiga', 'new-book')->name('books.create');
