<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\User\Notifications\SendWelcomeSms;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class RegisterTest extends ApiTestCase
{
    public function test_can_register_with_name_phone_email_password(): void
    {
        $payload = [
            'name' => 'Иван Иванов',
            'phone' => '+79991234567',
            'email' => 'ivan@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ];

        $response = $this->postJson($this->getApiUrl('/auth/register'), $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'data' => ['token', 'expires_in'],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Иван Иванов',
            'phone' => '+79991234567',
            'email' => 'ivan@example.com',
            'role' => UserRole::Customer->value,
        ]);
    }

    public function test_register_returns_token_that_works_for_me(): void
    {
        $payload = $this->validRegisterPayload();

        $token = $this->postJson($this->getApiUrl('/auth/register'), $payload)
            ->json('data.token');

        $this->withToken($token)
            ->getJson($this->getApiUrl('/auth/me'))
            ->assertOk()
            ->assertJsonPath('data.email', $payload['email']);
    }

    public function test_register_sends_welcome_sms(): void
    {
        Notification::fake();

        $payload = $this->validRegisterPayload();

        $this->postJson($this->getApiUrl('/auth/register'), $payload);

        $user = User::where('email', $payload['email'])->firstOrFail();

        Notification::assertSentTo($user, SendWelcomeSms::class);
    }

    #[DataProvider('invalidRegistrationProvider')]
    public function test_register_validates_input(array $payload, string $errorField): void
    {
        $this->postJson($this->getApiUrl('/auth/register'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([$errorField]);
    }

    public static function invalidRegistrationProvider(): array
    {
        $makeValid = function (): array {
            return [
                'name' => 'Test User',
                'phone' => '+7999'.random_int(1000000, 9999999),
                'email' => 'test_'.uniqid().'@example.com',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
            ];
        };

        return [
            'missing name' => [array_merge($makeValid(), ['name' => null]), 'name'],
            'name too short' => [array_merge($makeValid(), ['name' => 'A']), 'name'],
            'missing phone' => [array_merge($makeValid(), ['phone' => null]), 'phone'],
            'phone bad format' => [array_merge($makeValid(), ['phone' => '89991234567']), 'phone'],
            'phone too short' => [array_merge($makeValid(), ['phone' => '+7123']), 'phone'],
            'missing email' => [array_merge($makeValid(), ['email' => null]), 'email'],
            'invalid email' => [array_merge($makeValid(), ['email' => 'not-email']), 'email'],
            'missing password' => [array_merge($makeValid(), ['password' => null]), 'password'],
            'password not confirmed' => [array_merge($makeValid(), ['password_confirmation' => 'different@123']), 'password'],
            'password too weak' => [array_merge($makeValid(), ['password' => '123', 'password_confirmation' => '123']), 'password'],
        ];
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $payload = array_merge($this->validRegisterPayload(), ['email' => 'taken@example.com']);

        $this->postJson($this->getApiUrl('/auth/register'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+79990000000']);

        $payload = array_merge($this->validRegisterPayload(), ['phone' => '+79990000000']);

        $this->postJson($this->getApiUrl('/auth/register'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_cannot_register_as_admin(): void
    {
        $payload = array_merge($this->validRegisterPayload(), ['role' => 'admin']);

        $this->postJson($this->getApiUrl('/auth/register'), $payload)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'role' => UserRole::Customer->value,
        ]);
    }

    private function validRegisterPayload(): array
    {
        return [
            'name' => 'Test User',
            'phone' => '+7999'.random_int(1000000, 9999999),
            'email' => 'test_'.uniqid().'@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ];
    }
}
