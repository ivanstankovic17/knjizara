<?php

use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\UserController;
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
use App\Models\Book;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class)
    ->middleware('locale:sr,en')
    ->name('api.status');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:api')
    ->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['index', 'show']);

        // Izmene kataloga: prvo prijava (401), pa dozvola iz BookPolicy (403).
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('books', [BookController::class, 'store'])
                ->can('create', Book::class)
                ->middleware('idempotent')
                ->name('books.store');
            // Samo PATCH, bez PUT (odeljak 6.3).
            Route::patch('books/{book}', [BookController::class, 'update'])
                ->can('update', 'book')
                ->name('books.update');
            Route::delete('books/{book}', [BookController::class, 'destroy'])
                ->can('delete', 'book')
                ->name('books.destroy');
        });

        Route::apiResource('authors', AuthorController::class)
            ->only(['index', 'show']);
        Route::get('authors/{author}/books', AuthorBookController::class)
            ->name('authors.books');

        Route::apiResource('categories', CategoryController::class)
            ->only(['index', 'show'])
            ->parameters(['categories' => 'category:slug'])
            ->middleware('cache.headers:public;max_age=60;etag');

        // Autori i kategorije: menadžer ih održava (zahtev Z-7, odeljak 17.1).
        // Kao kod knjiga, samo PATCH, bez PUT (odeljak 6.3).
        Route::middleware(['auth:sanctum', 'can:catalog.manage'])->group(function () {
            Route::post('authors', [AuthorController::class, 'store'])
                ->name('authors.store');
            Route::patch('authors/{author}', [AuthorController::class, 'update'])
                ->name('authors.update');
            Route::delete('authors/{author}', [AuthorController::class, 'destroy'])
                ->name('authors.destroy');
            Route::post('categories', [CategoryController::class, 'store'])
                ->name('categories.store');
            Route::patch('categories/{category:slug}', [CategoryController::class, 'update'])
                ->name('categories.update');
            Route::delete('categories/{category:slug}', [CategoryController::class, 'destroy'])
                ->name('categories.destroy');
        });

        // Izveštaj se pravi u redu poslova: 202, pa provera statusa.
        // Dozvola bez modela: Gate reports.view (odeljak 12.2).
        Route::middleware(['auth:sanctum', 'can:reports.view'])->group(function () {
            Route::post('reports/sales', [ReportController::class, 'store'])
                ->name('reports.sales');
            Route::get('reports/{report}', [ReportController::class, 'show'])
                ->name('reports.show');
        });

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

        // Porudžbine: spisak je uvek sopstveni; pojedinačnu porudžbinu
        // proverava OrderPolicy u kontroleru (vlasnik ili menadžer).
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [OrderController::class, 'store'])
                ->middleware(['verified.api', 'idempotent'])->name('orders.store');
            Route::get('orders/{order}', [OrderController::class, 'show'])
                ->whereNumber('order')->name('orders.show');
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
                ->whereNumber('order')->name('orders.cancel');
        });

        // Upravljanje (glava 12): cela grupa traži dozvolu, pa kontroleri
        // ne moraju da je proveravaju.
        Route::prefix('admin')->name('admin.')->middleware('auth:sanctum')->group(function () {
            Route::middleware('can:orders.manage')->group(function () {
                Route::get('orders', [AdminOrderController::class, 'index'])
                    ->name('orders.index');
                Route::patch('orders/{order}', [AdminOrderController::class, 'update'])
                    ->whereNumber('order')->name('orders.update');
            });
            Route::middleware('can:users.manage')->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::patch('users/{user}', [UserController::class, 'update'])
                    ->whereNumber('user')->name('users.update');
            });
        });
    });
