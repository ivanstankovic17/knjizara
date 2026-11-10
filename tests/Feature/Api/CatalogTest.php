<?php

use App\Actions\PlaceOrder;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Sanctum::actingAs(User::factory()->manager()->create()));

it('menadžer dodaje knjigu sa autorima po redu', function () {
    $category = Category::factory()->create();
    [$prvi, $drugi] = Author::factory()->count(2)->create();

    $response = $this->postJson('/api/v1/books', [
        'title' => 'Most na Nišavi',
        'isbn' => '9788652133772',
        'price' => 1490,
        'category_id' => $category->id,
        'author_ids' => [$drugi->id, $prvi->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.authors.0.id', $drugi->id)
        ->assertJsonPath('data.stock', 0)
        ->assertJsonPath('data.can.update', true);
    $this->assertDatabaseHas('author_book', [
        'book_id' => $response->json('data.id'), 'author_id' => $prvi->id, 'position' => 2,
    ]);
});

it('odbija ISBN sa pogrešnom kontrolnom cifrom', function () {
    $this->postJson('/api/v1/books', ['isbn' => '9788652133779'])
        ->assertJsonValidationErrors('isbn');
});

it('briše knjigu koja nije prodavana (204)', function () {
    $book = Book::factory()->create();

    $this->deleteJson("/api/v1/books/{$book->id}")->assertNoContent();
    $this->assertModelMissing($book);
});

it('ne briše knjigu koja je u porudžbinama (409)', function () {
    $book = Book::factory()->create(['stock' => 5]);
    app(PlaceOrder::class)->handle(User::factory()->create(), [$book->id => 1]);

    $this->deleteJson("/api/v1/books/{$book->id}")
        ->assertConflict()
        ->assertJsonPath('status', 409);
    $this->assertModelExists($book);
});

it('otkazivanje vraća knjige na stanje, a drugo otkazivanje je 409', function () {
    $book = Book::factory()->create(['stock' => 5]);
    $order = app(PlaceOrder::class)->handle(User::factory()->create(), [$book->id => 2]);

    $this->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertOk()->assertJsonPath('data.status', 'cancelled');
    expect($book->fresh()->stock)->toBe(5);

    $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertConflict();
    expect($book->fresh()->stock)->toBe(5);
});
