<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $apiBase = '/api';

    protected function getApiUrl(string $path): string
    {
        return $this->apiBase . $path;
    }

    protected function getValidProductData(): array
    {
        return [
            'name'        => 'Test Pizza ' . uniqid(),
            'description' => 'A delicious test pizza.',
            'price'       => '1500',
            'weight'      => 450,
            'category'    => ProductCategory::Pizza->value,
        ];
    }

    protected function createUser(UserRole $role = UserRole::Customer, array $overrides = []): User
    {
        return User::factory()
            ->state(array_merge(['role' => $role->value], $overrides))
            ->create();
    }

    protected function getTokenForUser(User $user): string
    {
        return \Illuminate\Support\Facades\Auth::guard('api')->attempt([
            'email'    => $user->email,
            'password' => 'password',
        ]);
    }

    protected function adminToken(): string
    {
        return $this->getTokenForUser($this->createUser(UserRole::Admin));
    }

    protected function customerToken(): string
    {
        return $this->getTokenForUser($this->createUser(UserRole::Customer));
    }

    protected function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }
}
