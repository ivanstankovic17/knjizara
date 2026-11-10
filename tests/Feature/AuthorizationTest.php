<?php

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function prijavi(?string $role): void
{
    if ($role !== null) {
        Sanctum::actingAs(User::factory()->{$role}()->create());
    }
}

/*
 * Matrica „ko sme šta”: za svaku ulogu očekivan status (odeljak 12.1).
 * null je neprijavljen korisnik. {knjiga} je id knjige iz testa: na MySQL-u
 * brojač se ne vraća posle testa, pa prva knjiga nije uvek 1 (odeljak 16.6).
 */
dataset('matrica', [
    'gost menja knjigu' => [null, 'PATCH', '/api/v1/books/{knjiga}', 401],
    'kupac menja knjigu' => ['customer', 'PATCH', '/api/v1/books/{knjiga}', 403],
    'menadžer menja knjigu' => ['manager', 'PATCH', '/api/v1/books/{knjiga}', 200],
    'kupac traži izveštaj' => ['customer', 'POST', '/api/v1/reports/sales', 403],
    'menadžer traži izveštaj' => ['manager', 'POST', '/api/v1/reports/sales', 202],
    'kupac vidi porudžbine' => ['customer', 'GET', '/api/v1/admin/orders', 403],
    'menadžer vidi porudžbine' => ['manager', 'GET', '/api/v1/admin/orders', 200],
    'menadžer vidi korisnike' => ['manager', 'GET', '/api/v1/admin/users', 403],
    'administrator vidi korisnike' => ['admin', 'GET', '/api/v1/admin/users', 200],
]);

it('poštuje matricu dozvola',
    function (?string $role, string $method, string $uri, int $status) {
        $knjiga = Book::factory()->create();
        prijavi($role);

        $this->json($method, str_replace('{knjiga}', $knjiga->id, $uri),
            ['price' => 1290])->assertStatus($status);
    })->with('matrica');

it('tuđa porudžbina izgleda kao nepostojeća (404)', function () {
    $petar = User::factory()->create();
    $tudja = Order::factory()->create();
    Sanctum::actingAs($petar);

    $tudjaOdgovor = $this->getJson("/api/v1/orders/{$tudja->id}")
        ->assertNotFound();
    $nepostojeca = $this->getJson('/api/v1/orders/999')->assertNotFound();

    expect($tudjaOdgovor->json('detail'))
        ->toBe("Porudžbina {$tudja->id} ne postoji.")
        ->and($nepostojeca->json('detail'))->toBe('Porudžbina 999 ne postoji.');
    $this->postJson("/api/v1/orders/{$tudja->id}/cancel")->assertNotFound();
});

it('vlasnik i menadžer vide porudžbinu, a kupca vidi samo menadžer', function () {
    $order = Order::factory()->create();

    Sanctum::actingAs($order->user);
    $this->getJson("/api/v1/orders/{$order->id}")
        ->assertOk()->assertJsonMissingPath('data.customer');

    Sanctum::actingAs(User::factory()->manager()->create());
    $this->getJson("/api/v1/orders/{$order->id}")
        ->assertOk()->assertJsonPath('data.customer.id', $order->user_id);
});

it('administrator ne može da promeni svoju ulogu', function () {
    $ana = User::factory()->admin()->create();
    Sanctum::actingAs($ana);

    $this->patchJson("/api/v1/admin/users/{$ana->id}", ['role' => 'customer'])
        ->assertForbidden();
    expect($ana->fresh()->role->value)->toBe('admin');
});

it('svaka API ruta koja menja podatke ima proveru', function () {
    // Javne rute: nalozi i prijava.
    $javne = [
        'auth.register', 'auth.login', 'auth.forgot', 'auth.reset', 'tokens.store',
    ];

    // Prijava je dovoljna: korisnik radi nešto sa sopstvenim nalogom,
    // ili kontroler proverava Policy (Gate::authorize).
    $sopstvene = [
        'auth.logout', 'auth.verification.send', 'tokens.destroy',
        'auth.two-factor.store', 'auth.two-factor.confirm',
        'auth.two-factor.destroy',
        'orders.store', 'orders.cancel',
    ];

    $bezDozvole = collect(Router::getRoutes()->getRoutes())
        ->filter(fn (Route $r) => Str::startsWith($r->uri(), 'api/v1/')
            && array_diff($r->methods(), ['GET', 'HEAD']))
        ->map(fn (Route $r) => [
            'ime' => Str::after($r->getName(), 'api.v1.'),
            'middleware' => $r->gatherMiddleware(),
        ])
        ->reject(fn ($r) => in_array($r['ime'], $javne, true))
        ->each(fn ($r) => expect(in_array('auth:sanctum', $r['middleware'], true))
            ->toBeTrue("Ruta {$r['ime']} nema auth:sanctum."))
        ->reject(fn ($r) => collect($r['middleware'])
            ->contains(fn ($m) => Str::startsWith($m, 'can:')))
        ->pluck('ime');

    expect($bezDozvole->all())->toEqualCanonicalizing($sopstvene);
});
