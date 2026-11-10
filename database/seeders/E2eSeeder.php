<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Podaci za end-to-end testove (odeljak 14.9): početni podaci i kupac
 * sa poznatom lozinkom ("password", iz UserFactory).
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        User::factory()->customer()->create([
            'name' => 'Petar Kupac',
            'email' => 'petar@knjizara.test',
        ]);
    }
}
