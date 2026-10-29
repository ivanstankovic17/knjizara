<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Roman', 'Poezija', 'Istorija', 'Nauka',
            'Programiranje', 'Dečje knjige'];

        $rows = array_map(fn (string $name) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ], $names);

        Category::upsert($rows, uniqueBy: ['slug'], update: ['name']);
    }
}
