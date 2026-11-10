<?php

namespace App\Http;

use App\Exceptions\ConflictException;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Odgovor sa greškom u formatu Problem Details for HTTP APIs (RFC 9457).
 */
final class Problem
{
    public const BASE = 'https://knjizara.test/problemi/';

    private const MODELS = [
        Book::class => 'Knjiga',
        Author::class => 'Autor',
        Category::class => 'Kategorija',
        Order::class => 'Porudžbina',
    ];

    public static function from(Throwable $e, Request $request): JsonResponse
    {
        $p = match (true) {
            $e instanceof ValidationException => [
                'status' => 422,
                'type' => self::BASE.'neispravni-podaci',
                'title' => 'Podaci nisu ispravni',
                'detail' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ],
            $e instanceof ConflictException => [
                'status' => 409,
                'type' => self::BASE.$e->type(),
                'title' => $e->title(),
                'detail' => $e->detail(),
            ],
            $e instanceof AuthenticationException => [
                'status' => 401,
                'title' => 'Potrebna je prijava',
                'detail' => 'Zahtev nema ni ispravnu sesiju ni ispravan token za pristup.',
            ],
            $e instanceof HttpExceptionInterface => [
                'status' => $e->getStatusCode(),
                'detail' => self::httpDetail($e, $request),
            ],
            default => [
                'status' => 500,
                'detail' => 'Došlo je do greške na serveru. '
                    .'Pokušajte ponovo kasnije.',
            ],
        };

        $status = $p['status'];
        $problem = [
            'type' => $p['type'] ?? 'about:blank',
            'title' => $p['title'] ?? self::statusTitle($status),
            'status' => $status,
            'detail' => $p['detail'],
            'instance' => '/'.$request->path(),
            ...array_diff_key($p,
                array_flip(['status', 'type', 'title', 'detail'])),
            'request_id' => $request->attributes->get('request_id'),
        ];

        // Na razvojnom računaru, i samo tamo, uz grešku servera idu i detalji.
        if ($status >= 500 && config('app.debug')) {
            $problem['debug'] = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'at' => basename($e->getFile()).':'.$e->getLine(),
            ];
        }

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return new JsonResponse($problem, $status, [
            ...$headers,
            'Content-Type' => 'application/problem+json',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Opis HTTP greške za klijenta, bez imena klasa i putanja do fajlova.
     */
    private static function httpDetail(HttpExceptionInterface $e, Request $request): string
    {
        $previous = $e->getPrevious();

        if ($previous instanceof ModelNotFoundException) {
            $name = self::MODELS[$previous->getModel()] ?? 'Resurs';

            return sprintf('%s %s ne postoji.', $name, implode(', ', $previous->getIds()));
        }

        // Odbijanje iz Policy-ja (403, ili 404 sa denyAsNotFound): poruka je
        // napisana za klijenta, pa se prenosi kakva jeste (odeljak 12.5).
        // Policy koji vrati samo false daje Laravelovu englesku poruku.
        if ($previous instanceof AuthorizationException) {
            $message = $previous->getMessage();

            return in_array($message, ['', 'This action is unauthorized.'], true)
                ? 'Nemate dozvolu za ovu akciju.' : $message;
        }

        return match ($e->getStatusCode()) {
            404 => sprintf('Adresa %s ne postoji.', '/'.$request->path()),
            405 => sprintf('Metoda %s nije dozvoljena; dozvoljene su: %s.',
                $request->method(), $e->getHeaders()['Allow'] ?? '-'),
            429 => sprintf('Previše zahteva. Pokušajte ponovo za %s s.',
                $e->getHeaders()['Retry-After'] ?? '60'),
            419 => 'CSRF token nedostaje ili je istekao. Osvežite stranicu i pokušajte ponovo.',
            default => $e->getMessage() ?: self::statusTitle($e->getStatusCode()),
        };
    }

    private static function statusTitle(int $status): string
    {
        // 419 nije standardan HTTP status, nego Laravelov (odeljak 2.3).
        return $status === 419
            ? 'Page Expired'
            : (Response::$statusTexts[$status] ?? 'Error');
    }
}
