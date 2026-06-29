<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\User\Notifications\SendWelcomeSms;
use App\Modules\User\Requests\LoginRequest;
use App\Modules\User\Requests\RegisterRequest;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->string('name')->toString(),
            'phone'    => $request->string('phone')->toString(),
            'email'    => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role'     => UserRole::Customer,
        ]);

        $user->notify(new SendWelcomeSms($user->name));

        $token = $this->guard()->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = $this->guard()->attempt($credentials)) {
            return response()->json([
                'message' => 'Неверные учётные данные.',
            ], 401);
        }

        return $this->respondWithToken($token, $this->guard()->user());
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Успешный выход.']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($this->guard()->user()),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(
            $this->guard()->refresh(),
            $this->guard()->user(),
        );
    }

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'user'         => UserResource::make($user),
                'token'        => $token,
                'token_type'   => 'bearer',
                'expires_in'   => $this->guard()->factory()->getTTL() * 60,
            ],
        ], $status);
    }

    private function guard(): JWTGuard
    {
        return Auth::guard('api');
    }
}
