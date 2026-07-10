<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class LoginTest extends ApiTestCase
{
    public function test_can_login_with_valid_credentials(): void
    {
        User::factory()->create(attributes: [
            'email' => 'login@example.com',
            'password' => 'Password@123',
        ]);

        $response = $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'login@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(structure: [
                'data' => ['token', 'expires_in'],
            ])
            ->assertJsonPath(path: 'data.token', expect: fn ($t): bool => is_string(value: $t) && $t !== '');
    }

    public function test_login_returns_token_for_authenticated_user(): void
    {
        $user = User::factory()->create(attributes: ['password' => 'Password@123']);

        $token = $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => $user->email,
            'password' => 'Password@123',
        ])->json(key: 'data.token');

        $this->withToken($token)
            ->getJson($this->getApiUrl('/auth/me'))
            ->assertOk()
            ->assertJsonPath(path: 'data.email', expect: $user->email);
    }

    public function test_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath(path: 'message', expect: 'Неверные учётные данные.');
    }

    public function test_cannot_login_with_nonexistent_email(): void
    {
        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'nobody@example.com',
            'password' => 'Password@123',
        ])
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath(path: 'message', expect: 'Неверные учётные данные.');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['email', 'password']);
    }
}
