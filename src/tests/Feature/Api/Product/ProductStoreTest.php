<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

/**
 * Тесты для POST /api/products (создание).
 */
class ProductStoreTest extends ApiTestCase
{
    public function test_can_create_product(): void
    {
        $payload = $this->getValidProductData();

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment([
                'name'  => $payload['name'],
                'price' => [
                    'amount'   => 150000,   // 1500 рублей = 150000 центов
                    'rubles'   => 1500.0,
                    'currency' => 'RUB',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name'  => $payload['name'],
            'price' => 150000,
        ]);
    }

    /**
     * Проверка, что можно создать продукт с дробной ценой (копейками).
     */
    public function test_can_create_product_with_fractional_price(): void
    {
        $payload = array_merge($this->getValidProductData(), ['price' => '1500.99']);

        $this->postJson($this->getApiUrl('/products'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.price.amount', 150099)
            ->assertJsonPath('data.price.rubles', 1500.99);

        $this->assertDatabaseHas('products', ['price' => 150099]);
    }

    /**
     * @dataProvider invalidProductDataProvider
     */
    public function test_cannot_create_product_with_invalid_data(array $payload, string $errorField): void
    {
        $this->postJson($this->getApiUrl('/products'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([$errorField]);
    }

    public static function invalidProductDataProvider(): array
    {
        $valid = (new self())->getValidProductData();

        return [
            'missing name'        => [array_merge($valid, ['name' => null]), 'name'],
            'missing description' => [array_merge($valid, ['description' => null]), 'description'],
            'missing price'       => [array_merge($valid, ['price' => null]), 'price'],
            'missing weight'      => [array_merge($valid, ['weight' => null]), 'weight'],
            'missing category'    => [array_merge($valid, ['category' => null]), 'category'],
            'negative price'      => [array_merge($valid, ['price' => -100]), 'price'],
            'zero price'          => [array_merge($valid, ['price' => 0]), 'price'],
            'non-numeric price'   => [array_merge($valid, ['price' => 'expensive']), 'price'],
            'negative weight'     => [array_merge($valid, ['weight' => -50]), 'weight'],
            'invalid category'    => [array_merge($valid, ['category' => 'десерт']), 'category'],
            'too long name'       => [array_merge($valid, ['name' => str_repeat('x', 256)]), 'name'],
        ];
    }

    public function test_cannot_create_product_with_duplicate_name(): void
    {
        Product::factory()->create(['name' => 'Margherita']);

        $payload = array_merge($this->getValidProductData(), ['name' => 'Margherita']);

        $this->postJson($this->getApiUrl('/products'), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }
}
