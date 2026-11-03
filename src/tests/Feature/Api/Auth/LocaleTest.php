<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use Tests\Feature\Api\ApiTestCase;

final class LocaleTest extends ApiTestCase
{
    public function test_supported_accept_language_is_used(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->postJson($this->getApiUrl('/auth/login'), [])
            ->assertUnprocessable()
            ->assertJsonPath(path: 'errors.email.0', expect: 'The email field is required.');
    }

    public function test_unknown_accept_language_uses_configured_fallback(): void
    {
        config()->set(key: 'app.locale', value: 'en');

        $this->withHeader('Accept-Language', 'de-DE')
            ->postJson($this->getApiUrl('/auth/login'), [])
            ->assertUnprocessable()
            ->assertJsonPath(path: 'errors.email.0', expect: 'The email field is required.');
    }
}
