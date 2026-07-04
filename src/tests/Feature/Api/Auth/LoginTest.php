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
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'Password@123',
        ]);

        $response = $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'login@example.com',
            'password' => 'Password@123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'expires_in'],
            ])
            ->assertJsonPath('data.token', fn ($t) => is_string($t) && $t !== '');
    }

    public function test_login_returns_token_for_authenticated_user(): void
    {
        $user = User::factory()->create(['password' => 'Password@123']);

        $token = $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => $user->email,
            'password' => 'Password@123',
        ])->json('data.token');

        $this->withToken($token)
            ->getJson($this->getApiUrl('/auth/me'))
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Неверные учётные данные.');
    }

    public function test_cannot_login_with_nonexistent_email(): void
    {
        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'nobody@example.com',
            'password' => 'Password@123',
        ])
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Неверные учётные данные.');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson($this->getApiUrl('/auth/login'), [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
