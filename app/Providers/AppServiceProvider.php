<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Services\PriceFormatter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

        // Na produkciji sve adrese koje aplikacija pravi počinju sa https://
        // (linkovi u mejlovima, Location, preusmerenja; odeljak 13.6).
        URL::forceHttps($this->app->isProduction());

        // Svaka dozvola je i Gate istog imena: Gate::allows('reports.view'),
        // middleware can:reports.view, @can('reports.view') (odeljak 12.2).
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value,
                fn (User $user) => $user->hasPermission($permission));
        }

        // Najviše 60 zahteva u minuti po korisniku, odnosno po IP adresi
        // za neprijavljene klijente (middleware throttle:api).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Mejlovi za potvrdu adrese i novu lozinku, na srpskom (glava 11).
        VerifyEmail::toMailUsing(fn (object $user, string $url) => (new MailMessage)
            ->subject('Knjižara: potvrdite email adresu')
            ->greeting("Zdravo, {$user->name}!")
            ->line('Kliknite na dugme da potvrdite adresu. Posle toga možete da poručujete knjige.')
            ->action('Potvrdi adresu', $url)
            ->line('Link važi 60 minuta. Ako niste napravili nalog, zanemarite ovu poruku.'));

        // Link vodi na stranicu klijenta, koja šalje token API-ju.
        ResetPassword::toMailUsing(fn (object $user, string $token) => (new MailMessage)
            ->subject('Knjižara: nova lozinka')
            ->line('Neko je zatražio novu lozinku za vaš nalog.')
            ->action('Postavi novu lozinku', url('/lozinka/nova')
                .'?'.http_build_query(['token' => $token, 'email' => $user->email]))
            ->line('Link važi 60 minuta. Ako niste vi tražili novu lozinku, zanemarite ovu poruku.'));
    }
}
