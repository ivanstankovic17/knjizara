<?php

namespace App\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Scramble opisuje greške Laravelovim oblikom {"message": ...}, a knjižara
 * sve greške vraća u formatu RFC 9457 (odeljak 8.6, App\Http\Problem).
 * Ovaj transformer zamenjuje telo svakog odgovora sa greškom.
 */
class ProblemResponses implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $components = $document->components;
        $problem = Schema::fromType($components->addSchema('Problem',
            Schema::fromType(self::problem())));
        $validation = Schema::fromType($components->addSchema('ValidationProblem',
            Schema::fromType(self::validationProblem())));

        $responses = $components->responses;
        foreach ($document->paths as $path) {
            foreach ($path->operations as $operation) {
                array_push($responses, ...$operation->responses);
            }
        }

        foreach ($responses as $response) {
            if ($response instanceof Response && (int) $response->code >= 400) {
                $response->content = [];
                $response->setContent('application/problem+json',
                    (int) $response->code === 422 ? $validation : $problem);
            }
        }
    }

    /**
     * Polja svake greške; primer je pravi odgovor za POST /orders (409).
     */
    private static function problem(): ObjectType
    {
        $string = fn (string $opis) => (new StringType)->setDescription($opis);

        return (new ObjectType)
            ->addProperty('type', $string('URI vrste problema, ili about:blank.')
                ->example('https://knjizara.test/problemi/nema-na-stanju'))
            ->addProperty('title', $string('Kratak naziv vrste problema.')
                ->example('Knjige nema dovoljno na stanju'))
            ->addProperty('status', (new IntegerType)->example(409))
            ->addProperty('detail', $string('Opis ovog slučaja, za korisnika.')
                ->example('Knjige „Most na Nišavi” trenutno nema u traženoj količini.'))
            ->addProperty('instance', $string('Putanja zahteva.')
                ->example('/api/v1/orders'))
            ->addProperty('request_id', $string('Identifikator zahteva u logu servera.')
                ->example('2fa41461-0c68-4409-b313-483cc14d5536'))
            ->setRequired(['type', 'title', 'status', 'detail', 'instance']);
    }

    /**
     * Greška validacije (422): ista polja i poruke po poljima.
     */
    private static function validationProblem(): ObjectType
    {
        $errors = (new ObjectType)
            ->additionalProperties((new ArrayType)->setItems(new StringType))
            ->setDescription('Poruke po poljima zahteva.')
            ->example(['boja' => ['Nepoznat parametar.']]);

        $type = self::problem();
        $type->getProperty('type')->example('https://knjizara.test/problemi/neispravni-podaci');
        $type->getProperty('title')->example('Podaci nisu ispravni');
        $type->getProperty('status')->example(422);
        $type->getProperty('detail')->example('Nepoznat parametar.');
        $type->getProperty('instance')->example('/api/v1/books');

        return $type->addProperty('errors', $errors)
            ->setRequired(['type', 'title', 'status', 'detail', 'instance', 'errors']);
    }
}
