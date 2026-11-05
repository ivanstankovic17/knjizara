<?php

use App\Exceptions\ConflictException;
use App\Http\Middleware\AddResponseTime;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\ForceJsonAccept;
use App\Http\Middleware\Idempotent;
use App\Http\Middleware\RequireJsonBody;
use App\Http\Middleware\SetLocale;
use App\Http\Problem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->append(AddResponseTime::class);
        $middleware->api(prepend: [ForceJsonAccept::class, RequireJsonBody::class]);

        // Zahtevi sa stranica knjižare (isti domen) dobijaju sesiju i CSRF
        // zaštitu i na API rutama; ostali klijenti koriste tokene (glava 11).
        $middleware->statefulApi();

        $middleware->alias([
            'locale' => SetLocale::class,
            'idempotent' => Idempotent::class,
            'verified.api' => EnsureEmailVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sve greške API-ja imaju isti oblik: RFC 9457 (odeljak 8.6).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return Problem::from($e, $request);
            }
        });

        // Sukob sa stanjem podataka je očekivan ishod, a ne greška u kodu.
        $exceptions->dontReport(ConflictException::class);
    })->create();
