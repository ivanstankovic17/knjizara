<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Book;
use App\Models\User;

/**
 * Ko sme šta sa knjigama. Čitanje kataloga je javno, pa za njega
 * nema metoda; menja ga samo onaj ko ima dozvolu za katalog.
 */
class BookPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageCatalog);
    }

    public function update(User $user, Book $book): bool
    {
        return $user->hasPermission(Permission::ManageCatalog);
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->hasPermission(Permission::ManageCatalog);
    }
}
