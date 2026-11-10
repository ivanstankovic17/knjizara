<?php

namespace App\OpenApi;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\RouteInfo;

/**
 * Odgovori koje ne daje kontroler, nego middleware zajednički za sve
 * rute, pa ih Scramble ne vidi u kodu kontrolera.
 */
class CommonResponses implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $add = function (int $code, string $description) use ($operation) {
            foreach ($operation->responses as $response) {
                $response = $response instanceof Reference ? $response->resolve() : $response;
                if ((int) $response->code === $code) {
                    return;
                }
            }
            $operation->addResponse(Response::make($code)->setDescription($description));
        };

        // RequireJsonBody (odeljak 8.5): telo mora biti ispravan JSON.
        if ($operation->requestBodyObject !== null) {
            $add(400, 'Telo zahteva nije ispravan JSON');
            $add(415, 'Telo zahteva nije JSON');
        }

        // cache.headers sa etag (odeljak 2.6): uslovni zahtev sa If-None-Match
        // dobija 304 bez tela kada se odgovor nije promenio.
        $etag = collect($routeInfo->route->gatherMiddleware())
            ->contains(fn (string $m) => str_starts_with($m, 'cache.headers:')
                && str_contains($m, 'etag'));
        if ($etag) {
            $add(304, 'Nije promenjeno od verzije sa datim ETag-om');
        }

        // throttle:api (odeljak 7.6) na celoj grupi v1.
        $add(429, 'Previše zahteva; zaglavlje Retry-After kaže koliko čekati');
    }
}
