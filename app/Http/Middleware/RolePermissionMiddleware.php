<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionMiddleware
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->error('Unauthorized. Please login.', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $userRoles = $user->getRoleNames();

        $userPermissions = $user->getAllPermissions()->pluck('name');

        $requiredPermission = $this->resolvePermission($request);

        if (! $requiredPermission) {
            return $next($request);
        }

        if ($userPermissions->contains($requiredPermission)) {
            return $next($request);
        }

        return $this->error('Forbidden. You do not have access.', Response::HTTP_FORBIDDEN);
    }

    /**
     * Auto-resolve required permission from route name + method.
     */
    private function resolvePermission(Request $request): ?string
    {
        $route = $request->route()?->getName();
        $method = $request->method();

        if (! $route) {
            return null;
        }

        $parts = explode('.', $route);
        $resource = $parts[0] ?? null;
        $action = $parts[1] ?? null;

        $map = [
            'index' => 'view',
            'show' => 'view',
            'store' => 'create',
            'update' => 'edit',
            'destroy' => 'delete',

            'stats' => 'view',
            'today' => 'view',
            'checkIn' => 'create',
            'checkOut' => 'create',
            'memberHistory' => 'view',
            'freeze' => 'freeze',
            'unfreeze' => 'freeze',
            'renew' => 'create',
            'assignMember' => 'assign',
            'removeMember' => 'assign',
            'assignToUser' => 'assign',
            'permissions' => 'view',
        ];

        $verb = $map[$action] ?? $this->methodToVerb($method);

        return "{$verb} {$resource}";
    }

    /**
     * Fallback: map HTTP method to permission verb.
     */
    private function methodToVerb(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}
