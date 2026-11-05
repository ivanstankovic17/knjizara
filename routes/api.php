<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorBookController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class)
    ->middleware('locale:sr,en')
    ->name('api.status');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:api')
    ->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['index', 'store', 'show', 'destroy'])
            ->middlewareFor('store', 'idempotent');
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

        // Izveštaj se pravi u redu poslova: 202, pa provera statusa.
        Route::post('reports/sales', [ReportController::class, 'store'])
            ->name('reports.sales');
        Route::get('reports/{report}', [ReportController::class, 'show'])
            ->name('reports.show');

        // Nalozi i prijava (glava 11).
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('register', [AuthController::class, 'register'])
                ->middleware('throttle:6,1')->name('register');
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('forgot-password', [PasswordResetController::class, 'forgot'])
                ->middleware('throttle:6,1')->name('forgot');
            Route::post('reset-password', [PasswordResetController::class, 'reset'])
                ->name('reset');

            Route::middleware('auth:sanctum')->group(function () {
                Route::get('me', [AuthController::class, 'me'])->name('me');
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('email/verification-notification',
                    [AuthController::class, 'sendVerification'])
                    ->middleware('throttle:6,1')->name('verification.send');

                Route::post('two-factor', [TwoFactorController::class, 'store'])
                    ->name('two-factor.store');
                Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])
                    ->name('two-factor.confirm');
                Route::delete('two-factor', [TwoFactorController::class, 'destroy'])
                    ->name('two-factor.destroy');
            });
        });

        Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::delete('tokens/current', [TokenController::class, 'destroy'])
            ->middleware('auth:sanctum')->name('tokens.destroy');

        // Porudžbine: samo prijavljeni kupac, i samo svoje.
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [OrderController::class, 'store'])
                ->middleware(['verified.api', 'idempotent'])->name('orders.store');
            Route::get('orders/{order}', [OrderController::class, 'show'])
                ->whereNumber('order')->name('orders.show');
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
                ->whereNumber('order')->name('orders.cancel');
        });
    });
