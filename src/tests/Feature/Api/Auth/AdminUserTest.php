<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Enums\UserRole;
use Tests\Feature\Api\ApiTestCase;

final class AdminUserTest extends ApiTestCase
{
    public function test_guest_cannot_manage_users(): void
    {
        $this->getJson($this->getApiUrl('/admin/users'))->assertUnauthorized();
    }

    public function test_admin_can_list_users_with_pagination_limit(): void
    {
        $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->getJson($this->getApiUrl('/admin/users?per_page=100'))
            ->assertOk()->assertJsonPath(path: 'meta.per_page', expect: 100);
    }

    public function test_admin_can_list_users(): void
    {
        $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->getJson($this->getApiUrl('/admin/users'))
            ->assertOk()->assertJsonPath(path: 'data.0.email', expect: fn ($value): bool => is_string(value: $value));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $this->withHeaders($this->authHeader($this->customerToken()))
            ->getJson($this->getApiUrl('/admin/users'))
            ->assertForbidden();
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->adminUser();
        $this->withHeaders($this->authHeader($this->getTokenForUser($admin)))
            ->patchJson($this->getApiUrl("/admin/users/{$admin->id}/role"), ['role' => 'customer'])
            ->assertStatus(status: 409);
    }

    public function test_admin_cannot_demote_last_admin(): void
    {
        $admin = $this->adminUser();
        $target = $this->createUser(UserRole::Admin);
        $admin->delete();
        $this->withHeaders($this->authHeader($this->getTokenForUser($target)))
            ->patchJson($this->getApiUrl("/admin/users/{$target->id}/role"), ['role' => 'customer'])
            ->assertStatus(status: 409);
    }

    public function test_unknown_user_returns_not_found(): void
    {
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->patchJson($this->getApiUrl('/admin/users/999999/role'), ['role' => 'customer'])
            ->assertNotFound();
    }

    public function test_invalid_role_returns_unprocessable(): void
    {
        $user = $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->patchJson($this->getApiUrl("/admin/users/{$user->id}/role"), ['role' => 'invalid'])
            ->assertUnprocessable();
    }

    public function test_admin_can_change_user_role(): void
    {
        $user = $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->patchJson($this->getApiUrl("/admin/users/{$user->id}/role"), ['role' => 'admin'])
            ->assertOk()->assertJsonPath(path: 'data.role', expect: 'admin');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }
}
