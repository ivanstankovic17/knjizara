<?php

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->kupac = User::factory()->create();
    Sanctum::actingAs($this->kupac);
});

it('pravi porudžbinu i skida knjige sa stanja', function () {
    $a = Book::factory()->create(['price' => 990, 'stock' => 5]);
    $b = Book::factory()->create(['price' => 1490, 'stock' => 1]);

    $response = $this->postJson('/api/v1/orders', ['items' => [
        ['book_id' => $a->id, 'quantity' => 2],
        ['book_id' => $b->id, 'quantity' => 1],
    ]]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total', 2 * 990 + 1490)
        ->assertHeader('Location',
            route('api.v1.orders.show', $response->json('data.id')));

    expect($a->fresh()->stock)->toBe(3)
        ->and($b->fresh()->stock)->toBe(0);

    $this->assertDatabaseHas('orders', [
        'user_id' => $this->kupac->id,
        'status' => OrderStatus::Pending,
        'total' => 3470,
    ]);
    $this->assertDatabaseCount('order_items', 2);
});

it('kada jedne knjige nema, ne menja ništa (409)', function () {
    $ima = Book::factory()->create(['stock' => 5]);
    $nema = Book::factory()->create(['stock' => 1, 'title' => 'Most na Nišavi']);

    $this->postJson('/api/v1/orders', ['items' => [
        ['book_id' => $ima->id, 'quantity' => 2],
        ['book_id' => $nema->id, 'quantity' => 3],
    ]])
        ->assertConflict()
        ->assertJsonPath('type', 'https://knjizara.test/problemi/nema-na-stanju')
        ->assertJsonPath('detail',
            'Knjige „Most na Nišavi” trenutno nema u traženoj količini.');

    // Transakcija je poništena: ni porudžbine, ni skinutih primeraka.
    $this->assertDatabaseCount('orders', 0);
    expect($ima->fresh()->stock)->toBe(5);
});

it('odbija neispravnu korpu (422)', function (array $items, string $field) {
    $this->postJson('/api/v1/orders', ['items' => $items])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'prazna korpa' => [[], 'items'],
    'nepostojeća knjiga' => [
        [['book_id' => 999, 'quantity' => 1]], 'items.0.book_id'],
    'količina 0' => [fn () => [
        ['book_id' => Book::factory()->create()->id, 'quantity' => 0],
    ], 'items.0.quantity'],
    'ista knjiga dvaput' => [fn () => [
        ['book_id' => $id = Book::factory()->create()->id, 'quantity' => 1],
        ['book_id' => $id, 'quantity' => 1],
    ], 'items.0.book_id'],
]);

it('isti Idempotency-Key ne pravi drugu porudžbinu', function () {
    $book = Book::factory()->create(['stock' => 5]);
    $body = ['items' => [['book_id' => $book->id, 'quantity' => 1]]];
    $headers = ['Idempotency-Key' => 'porudzbina-0001'];

    $first = $this->postJson('/api/v1/orders', $body, $headers)->assertCreated();
    $second = $this->postJson('/api/v1/orders', $body, $headers)->assertCreated();

    $second->assertHeader('Idempotent-Replayed', 'true');
    expect($second->json('data.id'))->toBe($first->json('data.id'));
    $this->assertDatabaseCount('orders', 1);
    expect($book->fresh()->stock)->toBe(4);
});

it('traži potvrđenu email adresu', function () {
    Sanctum::actingAs(User::factory()->unverified()->create());

    $this->postJson('/api/v1/orders', ['items' => [['book_id' => 1, 'quantity' => 1]]])
        ->assertForbidden();
});
