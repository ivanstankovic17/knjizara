<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Prijava preko GitHub-a (OAuth 2.0, odeljak 11.6).
 */
class GithubLoginController extends Controller
{
    /**
     * GET /auth/github: preusmerenje na GitHub, sa nasumičnim "state".
     */
    public function redirect(): SymfonyRedirectResponse
    {
        // GitHub provajder podrazumevano traži opseg user:email.
        return Socialite::driver('github')->redirect();
    }

    /**
     * GET /auth/github/callback: GitHub vraća korisnika sa kodom, Socialite
     * proverava "state", menja kod za token i čita profil.
     */
    public function callback(Request $request): RedirectResponse
    {
        $github = Socialite::driver('github')->user();

        $user = User::where('github_id', $github->getId())->first()
            ?? User::where('email', $github->getEmail())->first();

        if ($user === null) {
            $user = User::create([
                'name' => $github->getName() ?: $github->getNickname(),
                'email' => $github->getEmail(),
                // Nalog nema lozinku koju korisnik zna; prijava ide preko
                // GitHub-a, a lozinka se može postaviti resetom.
                'password' => Str::random(40),
            ]);
        }

        // GitHub vraća samo potvrđenu primarnu adresu (opseg user:email).
        $user->forceFill([
            'github_id' => $github->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('account');
    }
}
