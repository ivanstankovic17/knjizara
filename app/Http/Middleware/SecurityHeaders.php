<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bezbednosna zaglavlja za svaki odgovor (odeljak 13.6).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // PHP ovo zaglavlje dodaje sam (expose_php); ne treba otkrivati verziju.
        header_remove('X-Powered-By');

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS samo preko HTTPS-a: pregledač ga preko HTTP-a ionako zanemaruje.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Stranica sa izuzetkom (Laravelova stranica za grešku pri razvoju)
        // ima svoje skripte i stilove u samom HTML-u, pa ne dobija CSP.
        if (! isset($response->exception)) {
            $headers->set('Content-Security-Policy', $this->policy($response));
        }

        return $response;
    }

    private function policy(Response $response): string
    {
        // JSON se ne prikazuje kao stranica: ništa ne sme da se učita ni ugradi.
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return "default-src 'none'; frame-ancestors 'none'";
        }

        $self = ["'self'"];
        $connect = ["'self'", $this->reverbOrigin()];

        // Vite u razvoju (npm run dev) služi skripte sa svog servera.
        if (Vite::isRunningHot()) {
            $hot = rtrim(file_get_contents(public_path('hot')));
            $self[] = $hot;
            $connect[] = $hot;
            $connect[] = preg_replace('/^http/', 'ws', $hot);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $self),
            // Laravelove stranice za greške (403, 404) imaju stil u HTML-u.
            'style-src '.implode(' ', $self)." 'unsafe-inline'",
            "img-src 'self' data:",
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }

    /** Adresa Reverb servera za WebSocket, na primer ws://localhost:8080. */
    private function reverbOrigin(): string
    {
        $o = config('broadcasting.connections.reverb.options');
        $scheme = ($o['scheme'] ?? 'https') === 'https' ? 'wss' : 'ws';

        return "{$scheme}://{$o['host']}:{$o['port']}";
    }
}
