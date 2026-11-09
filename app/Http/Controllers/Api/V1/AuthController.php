<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckCredentials;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Prijava sesijom, za JavaScript klijent na istom domenu (Sanctum SPA).
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user = User::create($data);

        // Listener iz Laravela šalje mejl sa linkom za potvrdu adrese.
        event(new Registered($user));

        // Sesija postoji samo za zahteve sa stranica knjižare (statefulApi).
        // Ostali klijenti se posle registracije prijavljuju sa POST /tokens.
        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        return (new UserResource($user->fresh()))->response()->setStatusCode(201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request, CheckCredentials $credentials): UserResource
    {
        abort_unless($request->hasSession(), 400,
            'Prijava sesijom radi samo sa stranica knjižare. Ostali klijenti koriste POST /api/v1/tokens.');

        $user = $credentials->handle($request);

        Auth::guard('web')->login($user, $request->boolean('remember'));

        // Nova sesija posle prijave: identifikator sesije koji je postojao
        // pre prijave (i koji je možda podmetnut) više ne važi.
        $request->session()->regenerate();

        return new UserResource($user);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): Response
    {
        // Klijent sa tokenom nema sesiju; on se odjavljuje sa DELETE /tokens/current.
        abort_unless($request->hasSession(), 400,
            'Odjava sesijom radi samo sa stranica knjižare. Token se opoziva sa DELETE /api/v1/tokens/current.');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * GET /api/v1/auth/me: prijavljeni korisnik (sesijom ili tokenom).
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * POST /api/v1/auth/email/verification-notification: ponovo pošalji link.
     */
    public function sendVerification(Request $request): Response
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->noContent(202);
    }
}
