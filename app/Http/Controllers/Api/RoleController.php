<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')
            ->with('permissions')
            ->get()
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'created_at' => $role->created_at,
            ]);

        return $this->success($roles, 'Roles retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], 'Role created successfully.', 201);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'users_count' => $role->users()->count(),
        ], 'Role retrieved successfully.');
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->fresh()->permissions->pluck('name'),
        ], 'Role updated successfully.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $protected = ['super-admin', 'admin', 'trainer', 'receptionist', 'member'];

        if (in_array($role->name, $protected)) {
            return $this->error('Cannot delete system roles.', 403);
        }

        $role->delete();

        return $this->success([], message: 'Role deleted successfully.');
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')
            ->pluck('name')
            ->toArray();

        return $this->success($permissions, 'Permissions retrieved successfully.');
    }

    public function assignToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->syncRoles([$request->role]);

        return $this->success([
            'user' => $user->name,
            'roles' => $user->getRoleNames(),
        ], 'Role assigned successfully.');
    }
}
