<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\User\Notifications\SendWelcomeSms;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

final class AuthService
{
    /**
     * @param  array{name: string, phone: string, email: string, password: string}  $input
     * @return array{user: User, token: string}  Возвращает юзера и JWT-токен.
     */
    public function register(array $input): array
    {
        $user = User::create([
            'name'     => $input['name'],
            'phone'    => $input['phone'],
            'email'    => $input['email'],
            'password' => $input['password'],
            'role'     => UserRole::Customer,
        ]);

        $user->notify(new SendWelcomeSms($user->name));

        $token = $this->guard()->login($user);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @return array{user: User, token: string}|null  null при неверных данных.
     */
    public function login(string $email, string $password): ?array
    {
        $token = $this->guard()->attempt([
            'email'    => $email,
            'password' => $password,
        ]);

        if ($token === false) {
            return null;
        }

        return [
            'user'  => $this->guard()->user(),
            'token' => $token,
        ];
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function refresh(): array
    {
        return [
            'user'  => $this->guard()->user(),
            'token' => $this->guard()->refresh(),
        ];
    }

    public function currentUser(): User
    {
        return $this->guard()->user();
    }

    public function expiresIn(): int
    {
        return $this->guard()->factory()->getTTL() * 60;
    }

    private function guard(): JWTGuard
    {
        return Auth::guard('api');
    }
}
