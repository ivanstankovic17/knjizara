<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Dvofaktorska prijava: uključivanje u dva koraka (tajna, pa potvrda
 * kodom) i isključivanje uz lozinku.
 */
class TwoFactorController extends Controller
{
    /**
     * Uključivanje dvofaktorske prijave.
     *
     * Vraća novu tajnu i URI za QR kod. Prijava je uključena tek posle
     * potvrde.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasTwoFactor()) {
            throw new ConflictHttpException('Dvofaktorska prijava je već uključena.');
        }

        $secret = Totp::secret();
        $user->forceFill(['two_factor_secret' => $secret])->save();

        return response()->json([
            'secret' => $secret,
            'otpauth_uri' => Totp::uri($secret, $user->email, config('app.name')),
        ]);
    }

    /**
     * Potvrda dvofaktorske prijave.
     *
     * Korisnik šalje kod iz aplikacije za autentifikaciju.
     */
    public function confirm(Request $request): Response
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);
        $user = $request->user();

        if ($user->two_factor_secret === null
            || ! Totp::verify($user->two_factor_secret, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'Kod nije ispravan.']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return response()->noContent();
    }

    /**
     * Isključivanje dvofaktorske prijave.
     *
     * Traži trenutnu lozinku.
     */
    public function destroy(Request $request): Response
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->noContent();
    }
}
