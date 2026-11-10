<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Testing\Fluent\AssertableJson;

it('vraća stranicu knjiga sortiranih po naslovu', function () {
    Book::factory()->create(['title' => 'Zvezda za kraj']);
    Book::factory()->create(['title' => 'Most na Nišavi']);
    Book::factory()->create(['title' => 'Kuća pod lipom']);

    $this->getJson('/api/v1/books')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.title', 'Kuća pod lipom')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure([
            'data' => [['id', 'title', 'slug', 'price', 'in_stock']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('ne otkriva stanje na lageru anonimnom korisniku', function () {
    $book = Book::factory()->create(['stock' => 7]);

    $this->getJson("/api/v1/books/{$book->id}")
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', fn (AssertableJson $data) => $data
                ->where('id', $book->id)
                ->where('in_stock', true)
                ->missing('stock')
                ->missing('can')
                ->etc()));
});

it('filtrira po kategoriji i ceni', function () {
    $roman = Category::factory()->create(['slug' => 'roman']);
    Book::factory()->for($roman)->create(['price' => 990]);
    Book::factory()->for($roman)->create(['price' => 2490]);
    Book::factory()->create(['price' => 990]);

    $this->getJson('/api/v1/books?category=roman&max_price=1000')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.price', 990);
});

it('pretražuje i po imenu autora', function () {
    $author = Author::factory()->create(['name' => 'Ivo Andrić']);
    Book::factory()->hasAttached($author, ['position' => 1])->create();
    Book::factory()->count(2)->create();

    $this->getJson('/api/v1/books?q=andri')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('odbija nepoznat parametar sa 422 u obliku problem+json', function () {
    $this->getJson('/api/v1/books?boja=plava')
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 422)
        ->assertJsonPath('errors.boja.0', 'Nepoznat parametar.');
});

it('za nepostojeću knjigu vraća 404 sa opisom na srpskom', function () {
    $this->getJson('/api/v1/books/999')
        ->assertNotFound()
        ->assertJsonPath('detail', 'Knjiga 999 ne postoji.')
        ->assertJsonPath('instance', '/api/v1/books/999');
});
