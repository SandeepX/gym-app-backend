<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $stats = Role::withCount('users')
            ->get()
            ->map(fn ($role) => [
                'role' => $role->name,
                'total' => $role->users_count,
            ]);

        return $this->success($stats, 'User stats retrieved.');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success([new UserResource($request->user()->load(['roles', 'permissions']))]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            $this->error('User not found', 404);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->success([], 'Password changed successfully.');
    }

    public function updateProfile(UpdateProfileRequest $request, $userId): JsonResponse
    {
        $validatedData = $request->validated();

        $user = User::find($userId);

        if (! $user) {
            $this->error('User Not found', 404);
        }

        $user->update($validatedData);

        return $this->success([new UserResource($user->fresh()?->load(['roles']))],
            'Update successful');
    }
}
