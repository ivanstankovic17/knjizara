<?php

namespace App\OpenApi;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Podešavanja OpenAPI specifikacije koju Scramble generiše iz koda
 * (glava 15). Poziva se iz AppServiceProvider::boot().
 */
class Documentation
{
    /** Grupe operacija u dokumentaciji, po prvom delu imena rute. */
    private const TAGS = [
        'books' => 'Katalog',
        'authors' => 'Katalog',
        'categories' => 'Katalog',
        'orders' => 'Porudžbine',
        'reports' => 'Izveštaji',
        'auth' => 'Nalog i prijava',
        'tokens' => 'Nalog i prijava',
        'admin' => 'Upravljanje',
    ];

    public static function register(): void
    {
        Scramble::registerExtension(ConflictExceptionToResponse::class);

        Scramble::configure()
            ->withOperationTransformers(CommonResponses::class)
            ->withDocumentTransformers([
                ProblemResponses::class,
                function (OpenApi $document) {
                    $document->info->title = 'API knjižare';
                    $document->info->description = self::description();
                },
            ])
            // Scramble daje samo JSON; stranicu sa Swagger UI-jem pravi
            // knjižara (routes/web.php), da bi radila uz CSP (odeljak 15.4).
            ->expose(ui: false, document: 'docs/api.json');

        // "api.v1.admin.orders.update" => "admin" => "Upravljanje"
        Scramble::resolveTagsUsing(function (RouteInfo $route) {
            $group = Str::of($route->route->getName())->after('api.v1.')->before('.');

            return [self::TAGS[(string) $group] ?? 'Ostalo'];
        });

        // Dokumentacija je javna, na svakom okruženju: API koriste i
        // spoljni klijenti, a rute za upravljanje štiti autorizacija,
        // ne to što ih niko ne zna (glava 12). Bez ovoga je samo lokalna.
        Gate::define('viewApiDocs', fn (?User $user) => true);
    }

    private static function description(): string
    {
        return <<<'MD'
            REST API internet knjižare: katalog, porudžbine, nalozi i upravljanje.
            Sve adrese su relativne u odnosu na `/api/v1`, a zahtevi i odgovori su JSON.

            **Prijava.** Stranice knjižare koriste sesiju (`POST /auth/login`).
            Ostali klijenti dobijaju token sa `POST /tokens` i šalju ga u zaglavlju
            `Authorization: Bearer <token>`; dugme *Authorize* na ovoj stranici radi isto.

            **Greške** su u formatu RFC 9457 (`application/problem+json`), sa poljima
            `type`, `title`, `status`, `detail` i `instance`. Greška validacije (`422`)
            ima i polje `errors` sa porukama po poljima.

            **Ograničenje:** 60 zahteva u minuti po korisniku, odnosno po IP adresi.
            Posle toga stiže `429` sa zaglavljem `Retry-After`.
            MD;
    }
}
