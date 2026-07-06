<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\DTO\RegisterInput;
use App\Modules\User\Models\User;
use App\Modules\User\Notifications\SendWelcomeSms;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

final class AuthService
{
    public function register(RegisterInput $input): string
    {
        $user = User::create([
            'name' => $input->name,
            'phone' => $input->phone,
            'email' => $input->email,
            'password' => $input->password,
        ]);

        $user->notify(new SendWelcomeSms(name: $user->name));

        return $this->guard()->login(user: $user);
    }

    /**
     * @return string|null JWT-токен, null при неверных учётных данных.
     */
    public function login(string $email, string $password): ?string
    {
        $token = $this->guard()->attempt(credentials: [
            'email' => $email,
            'password' => $password,
        ]);

        if ($token === false) {
            return null;
        }

        return $token;
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    public function refresh(): string
    {
        return $this->guard()->refresh();
    }

    public function currentUser(): ?User
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
