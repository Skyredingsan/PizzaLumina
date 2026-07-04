<?php

declare(strict_types=1);

namespace App\Modules\User\Middleware;

use App\Modules\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        try {
            $roleValue = Auth::guard('api')->payload()->get('role');
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Неавторизованный запрос. Укажите валидный Bearer-токен.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userRole = $roleValue !== null
            ? UserRole::tryFrom($roleValue)
            : null;

        if ($userRole === null) {
            return response()->json([
                'message' => 'Токен не содержит валидной роли. Обновите токен через /auth/refresh.',
            ], Response::HTTP_FORBIDDEN);
        }

        $allowedRoles = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! in_array($userRole, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Доступ запрещён. Требуется роль: '
                    .implode(', ', array_map(fn (UserRole $r) => $r->value, $allowedRoles)),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
