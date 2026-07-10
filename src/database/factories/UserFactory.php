<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+7##########'),  // +7 и 10 цифр
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(length: 10),
            'role' => UserRole::Customer->value,
        ];
    }

    public function admin(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'role' => UserRole::Admin->value,
        ]);
    }

    public function customer(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'role' => UserRole::Customer->value,
        ]);
    }

    public function guest(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'role' => UserRole::Guest->value,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(state: fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
