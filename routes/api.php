<?php

use App\Http\Controllers\Api\V1\AuthorBookController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\StatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/status', StatusController::class)
    ->middleware('locale:sr,en')
    ->name('api.status');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:api')
    ->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['index', 'store', 'show', 'destroy']);
        // Samo PATCH, bez PUT (odeljak 6.3).
        Route::patch('books/{book}', [BookController::class, 'update'])
            ->name('books.update');

        Route::apiResource('authors', AuthorController::class)
            ->only(['index', 'show']);
        Route::get('authors/{author}/books', AuthorBookController::class)
            ->name('authors.books');

        Route::apiResource('categories', CategoryController::class)
            ->only(['index', 'show'])
            ->parameters(['categories' => 'category:slug'])
            ->middleware('cache.headers:public;max_age=60;etag');
    });
