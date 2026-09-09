<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Enums\UserRole;
use Tests\Feature\Api\ApiTestCase;

final class AdminUserTest extends ApiTestCase
{
    public function test_admin_can_list_users(): void
    {
        $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->getJson($this->getApiUrl('/admin/users'))
            ->assertOk()->assertJsonPath('data.0.email', fn ($value): bool => is_string($value));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $this->withHeaders($this->authHeader($this->customerToken()))
            ->getJson($this->getApiUrl('/admin/users'))
            ->assertForbidden();
    }

    public function test_admin_can_change_user_role(): void
    {
        $user = $this->createUser(UserRole::Customer);
        $this->withHeaders($this->authHeader($this->adminToken()))
            ->patchJson($this->getApiUrl("/admin/users/{$user->id}/role"), ['role' => 'admin'])
            ->assertOk()->assertJsonPath('data.role', 'admin');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }
}
