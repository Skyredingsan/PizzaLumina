<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class ProductStoreTest extends ApiTestCase
{
    public function test_can_create_product(): void
    {
        $payload = $this->getValidProductData();

        $response = $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(status: Response::HTTP_CREATED)
            ->assertJsonPath(path: 'data.name', expect: $payload['name']);
    }

    public function test_can_create_product_with_fractional_price(): void
    {
        $payload = array_merge($this->getValidProductData(), [
            'price' => '1500.99',
            'name' => 'Пицца с дробной ценой',
        ]);

        $response = $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(status: Response::HTTP_CREATED)
            ->assertJsonPath(path: 'data.price.amount', expect: 150099)
            ->assertJsonPath(path: 'data.price.rubles', expect: 1500.99);

        $this->assertDatabaseHas('products', ['price' => 150099]);
    }

    public function test_cannot_create_product_without_name(): void
    {
        $payload = $this->getValidProductData();
        unset($payload['name']);

        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $payload)
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['name']);
    }

    public function test_cannot_create_product_with_duplicate_name(): void
    {
        $product = Product::factory()->create();

        $payload = $this->getValidProductData();
        $payload['name'] = $product->name;

        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $payload)
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['name']);
    }
}
