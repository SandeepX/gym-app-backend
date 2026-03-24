<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController
{
    use ApiResponseTrait;

    /**
     * Get all users filtered by role.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)
            )
            )
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->is_active, fn ($q) => $q->where('is_active', $request->boolean('is_active'))
            )
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(
            UserResource::collection($users),
            'Users retrieved successfully.'
        );
    }

    /**
     * Get users grouped by role.
     */
    public function byRole(): JsonResponse
    {
        $roles = Role::with(['users' => fn ($q) => $q->select('users.id', 'name', 'email', 'phone', 'is_active'),
        ])->get()->map(fn ($role) => [
            'role' => $role->name,
            'total' => $role->users->count(),
            'users' => $role->users,
        ]);

        return $this->success($roles, 'Users grouped by role.');
    }

    /**
     * Get stats per role.
     */
    public function stats(): JsonResponse
    {
        $stats = Role::withCount('users')->get()->map(fn ($role) => [
            'role' => $role->name,
            'total' => $role->users_count,
        ]);

        return $this->success($stats, 'User stats retrieved.');
    }
}
