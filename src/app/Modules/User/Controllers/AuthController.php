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
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->toRegisterInput());

        return $this->respondWithToken(
            token: $result['token'],
            status: Response::HTTP_CREATED,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($result === null) {
            return response()->json([
                'message' => 'Неверные учётные данные.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken(
            token: $result['token'],
        );
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json(['message' => 'Успешный выход.']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($this->auth->currentUser()),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->auth->refresh();

        return $this->respondWithToken(
            token: $result['token'],
        );
    }

    private function respondWithToken(string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token'      => $token,
                'expires_in' => $this->auth->expiresIn(),
            ],
        ], $status);
    }
}
