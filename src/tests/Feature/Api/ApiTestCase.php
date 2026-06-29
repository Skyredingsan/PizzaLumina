<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Базовый класс для API-тестов.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $apiBase = '/api';

    protected function getApiUrl(string $path): string
    {
        return $this->apiBase . $path;
    }

    /**
     * Валидные данные продукта для использования в тестах.
     */
    protected function getValidProductData(): array
    {
        return [
            'name'        => 'Test Pizza ' . uniqid('', true),
            'description' => 'A delicious test pizza.',
            'price'       => '1500',  // string → MoneyCast воспримет как рубли
            'weight'      => 450,
            'category'    => ProductCategory::Pizza->value,
        ];
    }

}
