<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Korisnici i uloge, za administratora.
 */
class UserController extends Controller
{
    /**
     * Spisak korisnika.
     *
     * Filter role.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'role' => ['sometimes', Rule::enum(Role::class)],
        ]);

        $users = User::query()
            ->when($data['role'] ?? null, fn ($q, $r) => $q->where('role', $r))
            ->orderBy('id')
            ->paginate(20);

        return UserResource::collection($users);
    }

    /**
     * Promena uloge korisnika.
     *
     * Administrator ne može da menja sopstvenu ulogu (403).
     */
    public function update(Request $request, User $user): UserResource
    {
        Gate::authorize('changeRole', $user);

        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user->forceFill(['role' => $data['role']])->save();

        return new UserResource($user);
    }
}
