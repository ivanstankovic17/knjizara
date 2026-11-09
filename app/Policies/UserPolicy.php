<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Upravljanje korisnicima: samo administrator.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Administrator ne menja sopstvenu ulogu: tako knjižara ne može
     * slučajno da ostane bez ijednog administratora.
     */
    public function changeRole(User $user, User $target): Response
    {
        if (! $user->hasPermission(Permission::ManageUsers)) {
            return Response::deny('Uloge menja samo administrator.');
        }

        return $user->id === $target->id
            ? Response::deny('Sopstvenu ulogu ne možete da promenite.')
            : Response::allow();
    }
}
