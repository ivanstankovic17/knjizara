<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    private const PRVO = ['Senka', 'Pesma', 'Tajna', 'Leto', 'Reka',
        'Kuća', 'Pismo', 'Zvezda', 'Tišina', 'Most'];

    private const DRUGO = ['starog grada', 'na Nišavi', 'bez imena',
        'u magli', 'iz detinjstva', 'pod lipom', 'za kraj', 'sa juga'];

    public function definition(): array
    {
        $title = fake()->randomElement(self::PRVO).' '
            .fake()->randomElement(self::DRUGO);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'
                .fake()->unique()->numerify('####'),
            'isbn' => fake()->unique()->isbn13(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(8, 40) * 100 - 10,
            'stock' => fake()->numberBetween(0, 30),
            'published_year' => fake()->numberBetween(1950, 2026),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => 0]);
    }
}
