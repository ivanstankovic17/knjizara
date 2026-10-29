<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        User::factory()->admin()->create([
            'name' => 'Ana Petrović',
            'email' => 'ana@knjizara.test',
        ]);
        User::factory(10)->create();

        $authors = Author::factory(20)->create();

        Book::factory(60)
            ->recycle(Category::all())
            ->create()
            ->each(function (Book $book) use ($authors) {
                $picked = $authors->random(fake()->numberBetween(1, 2));

                foreach ($picked->values() as $i => $author) {
                    $book->authors()->attach($author, [
                        'position' => $i + 1,
                    ]);
                }
            });
    }
}
