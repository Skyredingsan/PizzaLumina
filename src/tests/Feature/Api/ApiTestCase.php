<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'name' => 'Test Pizza',
            'description' => 'A delicious test pizza.',
            'price' => 1500,
            'weight' => 450,
            'category' => 'пицца',
        ];
    }
}
