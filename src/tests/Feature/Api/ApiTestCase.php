<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\Product\Services\ProductCacheService;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $apiBase = '/api';

    private ?User $cachedCustomer = null;
    private ?User $cachedAdmin = null;
    private ?string $cachedCustomerToken = null;
    private ?string $cachedAdminToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flushApplicationCache();
    }

    protected function getApiUrl(string $path): string
    {
        return $this->apiBase.$path;
    }

    protected function getValidProductData(): array
    {
        return [
            'name' => 'Test Pizza '.uniqid(),
            'description' => 'A delicious test pizza.',
            'price' => '1500',
            'weight' => 450,
            'category' => ProductCategory::Pizza->value,
        ];
    }

    protected function createUser(UserRole $role = UserRole::Customer, array $overrides = []): User
    {
        return User::factory()
            ->state(state: array_merge(['role' => $role->value], $overrides))
            ->create();
    }

    protected function getTokenForUser(User $user): string
    {
        return Auth::guard('api')->attempt([
            'email' => $user->email,
            'password' => 'password',
        ]);
    }

    protected function customerToken(): string
    {
        if ($this->cachedCustomerToken === null) {
            $this->cachedCustomer = $this->createUser(UserRole::Customer);
            $this->cachedCustomerToken = $this->getTokenForUser($this->cachedCustomer);
        }

        return $this->cachedCustomerToken;
    }

    protected function adminToken(): string
    {
        if ($this->cachedAdminToken === null) {
            $this->cachedAdmin = $this->createUser(UserRole::Admin);
            $this->cachedAdminToken = $this->getTokenForUser($this->cachedAdmin);
        }

        return $this->cachedAdminToken;
    }

    protected function customerUser(): User
    {
        $this->customerToken();

        return $this->cachedCustomer;
    }

    protected function adminUser(): User
    {
        $this->adminToken();

        return $this->cachedAdmin;
    }

    protected function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    protected function flushApplicationCache(): void
    {
        try {
            Cache::tags([ProductCacheService::TAG])->flush();
        } catch (\Throwable) {
        }
    }
}
