<?php

namespace App\Providers;

use App\Services\PriceFormatter;
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
        //
    }
}
