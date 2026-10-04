<?php

declare(strict_types=1);

namespace App\Modules\User\Middleware;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('api')->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => __(key: 'api.unauthorized'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userRole = $user->role;

        $allowedRoles = array_map(
            callback: UserRole::from(...),
            array: $roles,
        );

        if (! in_array(needle: $userRole, haystack: $allowedRoles, strict: true)) {
            return response()->json([
                'message' => __(key: 'api.forbidden_role', replace: ['role' => implode(separator: ', ', array: array_map(callback: fn (UserRole $r) => $r->value, array: $allowedRoles))]),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
