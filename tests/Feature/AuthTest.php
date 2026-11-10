<?php

use App\Models\User;
use App\Support\Totp;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GithubUser;

it('izdaje token koji važi 30 dana', function () {
    $user = User::factory()->create(['email' => 'ana@primer.test']);

    $token = $this->postJson('/api/v1/tokens', [
        'email' => 'ana@primer.test', 'password' => 'password',
        'device_name' => 'test',
    ])->assertCreated()->json('token');

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()->assertJsonPath('data.id', $user->id);

    $this->travel(31)->days();
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('za pogrešnu lozinku i nepostojeći nalog daje istu poruku',
    function (string $email) {
        User::factory()->create(['email' => 'ana@primer.test']);

        $this->postJson('/api/v1/tokens', [
            'email' => $email, 'password' => 'pogresna', 'device_name' => 'test',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Pogrešan email ili lozinka.');
    })->with(['ana@primer.test', 'niko@primer.test']);

it('posle pet promašaja odbija i ispravnu lozinku (429)', function () {
    User::factory()->create(['email' => 'ana@primer.test']);
    $pokusaj = fn (string $password) => $this->postJson('/api/v1/tokens', [
        'email' => 'ana@primer.test', 'password' => $password,
        'device_name' => 'test',
    ]);

    foreach (range(1, 5) as $i) {
        $pokusaj('pogresna')->assertUnprocessable();
    }
    $pokusaj('password')->assertTooManyRequests()->assertHeader('Retry-After');

    $this->travel(61)->seconds();
    $pokusaj('password')->assertCreated();
});

it('sa uključenom 2FA traži važeći kod', function () {
    $this->freezeTime();
    $secret = Totp::secret();
    User::factory()->create([
        'email' => 'milan@primer.test',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);
    $prijava = fn (array $extra = []) => $this->postJson('/api/v1/tokens', [
        'email' => 'milan@primer.test', 'password' => 'password',
        'device_name' => 'test',
        ...$extra,
    ]);

    $prijava()->assertJsonValidationErrors('code');
    $prijava(['code' => '000000'])
        ->assertJsonPath('errors.code.0', 'Kod nije ispravan.');
    $prijava(['code' => Totp::code($secret)])->assertCreated();
});

it('prijava sesijom radi samo sa stranica knjižare', function () {
    User::factory()->create(['email' => 'ana@primer.test']);
    $podaci = ['email' => 'ana@primer.test', 'password' => 'password'];

    // Bez zaglavlja Origin: zahtev ne dobija sesiju (klijent sa tokenom).
    $this->postJson('/api/v1/auth/login', $podaci)->assertBadRequest();

    // Sa stranice knjižare: sesija, pa CSRF provera.
    $this->withHeader('Origin', config('app.url'))
        ->postJson('/api/v1/auth/login', $podaci)
        ->assertOk();
    $this->assertAuthenticated('web');
});

it('GitHub prijava pravi nalog sa potvrđenom adresom', function () {
    Socialite::fake('github', (new GithubUser)->map([
        'id' => 40233063,
        'name' => 'Jelena Marković',
        'nickname' => 'jelenam',
        'email' => 'jelena@primer.test',
    ]));

    $this->get('/auth/github/callback?code=abc&state=xyz')
        ->assertRedirect(route('account'));

    $jelena = User::where('github_id', 40233063)->sole();
    expect($jelena)
        ->email->toBe('jelena@primer.test')
        ->hasVerifiedEmail()->toBeTrue();
    $this->assertAuthenticatedAs($jelena);
});

it('GitHub prijava se vezuje za postojeći nalog sa istim emailom', function () {
    $ana = User::factory()->create(['email' => 'ana@primer.test']);
    Socialite::fake('github', (new GithubUser)->map([
        'id' => 111, 'name' => 'Ana', 'email' => 'ana@primer.test',
    ]));

    $this->get('/auth/github/callback')->assertRedirect();

    expect($ana->fresh()->github_id)->toBe('111');
    $this->assertDatabaseCount('users', 1);
});
