<?php

namespace App\Providers;

use App\Services\PriceFormatter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PriceFormatter::class, function () {
            return new PriceFormatter(config('knjizara.currency'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lenjo učitavanje, tiho odbačeni i nepostojeći atributi
        // bacaju izuzetak, ali samo van produkcije.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Najviše 60 zahteva u minuti po korisniku, odnosno po IP adresi
        // za neprijavljene klijente (middleware throttle:api).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
