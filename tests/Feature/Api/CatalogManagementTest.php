<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

/*
 * Menadžer održava autore i kategorije (zahtev Z-7, odeljak 17.1).
 */

beforeEach(function () {
    Sanctum::actingAs(User::factory()->manager()->create());
});

it('pravi autora i vraća adresu u zaglavlju Location', function () {
    $response = $this->postJson('/api/v1/authors', [
        'name' => 'Isidora Sekulić', 'bio' => 'Pripovedač i esejista.',
    ])->assertCreated();

    $author = Author::sole();
    $response
        ->assertHeader('Location', route('api.v1.authors.show', $author))
        ->assertJsonPath('data.name', 'Isidora Sekulić')
        ->assertJsonPath('data.bio', 'Pripovedač i esejista.')
        ->assertJsonPath('data.books_count', 0);
});

it('menja samo poslata polja autora', function () {
    $author = Author::factory()->create(['name' => 'I. Sekulić', 'bio' => 'Stara.']);

    $this->patchJson("/api/v1/authors/{$author->id}", ['name' => 'Isidora Sekulić'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Isidora Sekulić')
        ->assertJsonPath('data.bio', 'Stara.');
});

it('ne briše autora koji ima knjige', function () {
    $author = Author::factory()->create();
    Book::factory(3)->hasAttached($author, ['position' => 1])->create();

    $this->deleteJson("/api/v1/authors/{$author->id}")
        ->assertStatus(409)
        ->assertJsonPath('type', 'https://knjizara.test/problemi/ima-knjige')
        ->assertJsonPath('detail',
            'Autor ima 3 knjige. Prvo premestite ili obrišite knjige.');

    $this->assertModelExists($author);
});

it('briše autora bez knjiga', function () {
    $author = Author::factory()->create();

    $this->deleteJson("/api/v1/authors/{$author->id}")->assertNoContent();

    $this->assertModelMissing($author);
});

it('pravi kategoriju sa slugom od imena', function () {
    $this->postJson('/api/v1/categories', ['name' => 'Dečje knjige za tinejdžere'])
        ->assertCreated()
        ->assertHeader('Location',
            route('api.v1.categories.show', 'decje-knjige-za-tinejdzere'))
        ->assertJsonPath('data.slug', 'decje-knjige-za-tinejdzere');
});

it('odbija kategoriju čiji slug već postoji', function () {
    Category::factory()->create(['name' => 'Roman', 'slug' => 'roman']);

    $this->postJson('/api/v1/categories', ['name' => 'ROMAN'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'Kategorija sa tim imenom već postoji.');
});

it('menja ime kategorije, a slug ostaje isti', function () {
    Category::factory()->create(['name' => 'Roman', 'slug' => 'roman']);

    $this->patchJson('/api/v1/categories/roman', ['name' => 'Romani'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Romani')
        ->assertJsonPath('data.slug', 'roman');
});

it('posle izmene kategorije briše keš spiska kategorija', function () {
    Category::factory()->create(['name' => 'Roman', 'slug' => 'roman']);
    $this->getJson('/api/v1/categories')->assertJsonPath('data.0.name', 'Roman');
    expect(Cache::has(Category::CACHE_KEY))->toBeTrue();

    $this->patchJson('/api/v1/categories/roman', ['name' => 'Romani'])->assertOk();

    $this->getJson('/api/v1/categories')->assertJsonPath('data.0.name', 'Romani');
});

it('ne briše kategoriju koja ima knjige', function () {
    $category = Category::factory()->create(['slug' => 'roman']);
    Book::factory()->for($category)->create();

    $this->deleteJson('/api/v1/categories/roman')
        ->assertStatus(409)
        ->assertJsonPath('title', 'Kategorija ne može da se obriše')
        ->assertJsonPath('detail',
            'Kategorija ima 1 knjigu. Prvo premestite ili obrišite knjige.');
});

it('kupcu ne dozvoljava izmene kataloga', function () {
    Sanctum::actingAs(User::factory()->customer()->create());
    $author = Author::factory()->create();

    $this->postJson('/api/v1/authors', ['name' => 'X'])->assertForbidden();
    $this->patchJson("/api/v1/authors/{$author->id}", ['name' => 'X'])
        ->assertForbidden();
    $this->postJson('/api/v1/categories', ['name' => 'X'])->assertForbidden();
});
