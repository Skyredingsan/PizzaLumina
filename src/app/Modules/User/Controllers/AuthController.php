<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\LoginRequest;
use App\Modules\User\Requests\RegisterRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->auth->register($request->toRegisterInput());

        return $this->respondWithToken(
            token: $token,
            status: Response::HTTP_CREATED,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->auth->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($token === null) {
            return response()->json([
                'message' => 'Неверные учётные данные.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken(token: $token);
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Успешный выход.']);
    }

    public function me(): JsonResponse
    {
        $user = $this->auth->currentUser();

        if ($user === null) {
            return response()->json([
                'message' => 'Пользователь не найден.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'data' => UserResource::make($user),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(token: $this->auth->refresh());
    }

    private function respondWithToken(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $token,
                'expires_in' => $this->auth->expiresIn(),
            ],
        ], $status);
    }
}
