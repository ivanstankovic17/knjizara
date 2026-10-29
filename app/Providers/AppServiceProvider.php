<?php

namespace App\Providers;

use App\Services\PriceFormatter;
use Illuminate\Database\Eloquent\Model;
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
    }
}
