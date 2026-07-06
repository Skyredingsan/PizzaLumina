<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class MeTest extends ApiTestCase
{
    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create(attributes: [
            'name' => 'Test Customer',
            'phone' => '+79991234567',
        ]);

        $token = $this->getTokenForUser($user);

        $this->withToken($token)
            ->getJson($this->getApiUrl('/auth/me'))
            ->assertOk()
            ->assertJsonPath(path: 'data.id', expect: $user->id)
            ->assertJsonPath(path: 'data.name', expect: 'Test Customer')
            ->assertJsonPath(path: 'data.phone', expect: '+79991234567')
            ->assertJsonPath(path: 'data.role', expect: UserRole::Customer->value);
    }

    public function test_me_without_token_returns_unauthorized(): void
    {
        $this->getJson($this->getApiUrl('/auth/me'))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_me_with_invalid_token_returns_unauthorized(): void
    {
        $this->withToken('invalid.token.here')
            ->getJson($this->getApiUrl('/auth/me'))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }
}
