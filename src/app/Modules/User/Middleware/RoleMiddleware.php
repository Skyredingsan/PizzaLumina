<?php

declare(strict_types=1);

namespace App\Modules\User\Middleware;

use App\Modules\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки роли пользователя.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Достаём пользователя из guard'а 'api' (без инъекции в конструктор —
        // контейнер Laravel не умеет резолвить JWTGuard напрямую).
        $user = Auth::guard('api')->user();

        if (! $user) {
            return response()->json([
                'message' => 'Не аутентифицирован.',
            ], 401);
        }

        $allowedRoles = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Доступ запрещён. Требуется роль: '
                    . implode(', ', array_map(fn (UserRole $r) => $r->value, $allowedRoles)),
            ], 403);
        }

        return $next($request);
    }
}
