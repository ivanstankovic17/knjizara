<?php

namespace App\Actions;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Proverava email, lozinku i, ako je uključen, kod druge faze.
 * Zajednička je za prijavu sesijom i za izdavanje tokena.
 */
class CheckCredentials
{
    /** Najviše 5 neuspelih pokušaja u minuti za isti email i IP adresu. */
    private const MAX_ATTEMPTS = 5;

    public function handle(Request $request): User
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string', 'size:6'],
        ]);

        $key = 'prijava:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw new ThrottleRequestsException(
                'Previše pokušaja prijave.', null, ['Retry-After' => $seconds]);
        }

        $user = User::where('email', $data['email'])->first();

        // I za nepostojeći nalog računa se heš, da se po vremenu odgovora
        // ne bi moglo zaključiti koji emailovi imaju nalog.
        $valid = Hash::check($data['password'], $user->password ?? self::dummyHash());
        if (! $user || ! $valid) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages([
                'email' => 'Pogrešan email ili lozinka.',
            ]);
        }

        if ($user->hasTwoFactor()) {
            if (empty($data['code'])) {
                throw ValidationException::withMessages([
                    'code' => 'Unesite šestocifreni kod iz aplikacije za autentifikaciju.',
                ]);
            }
            if (! Totp::verify($user->two_factor_secret, $data['code'])) {
                RateLimiter::hit($key);
                throw ValidationException::withMessages(['code' => 'Kod nije ispravan.']);
            }
        }

        RateLimiter::clear($key);

        // Heš sa starom cenom (BCRYPT_ROUNDS) se zamenjuje novim, sada
        // kada je lozinka poznata.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $data['password']])->save();
        }

        return $user;
    }

    private static function dummyHash(): string
    {
        static $hash;

        return $hash ??= Hash::make(Str::random(32));
    }
}
