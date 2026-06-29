<?php

namespace Tests\Feature\Api\Product;

use Tests\Feature\Api\ApiTestCase;
use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductStoreTest extends ApiTestCase
{
    public function test_can_create_product(): void
    {
        $payload = $this->getValidProductData();

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment([
                'name' => $payload['name'],
            ]);
    }

    public function test_can_create_product_with_fractional_price(): void
    {
        $payload = array_merge($this->getValidProductData(), [
            'price' => '1500.99',
            'name'  => 'Пицца с дробной ценой',
        ]);

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.price.amount', 150099)
            ->assertJsonPath('data.price.rubles', 1500.99);

        $this->assertDatabaseHas('products', ['price' => 150099]);
    }

    public function test_cannot_create_product_without_name(): void
    {
        $payload = $this->getValidProductData();
        unset($payload['name']);

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_product_with_duplicate_name(): void
    {
        $product = Product::factory()->create();

        $payload = $this->getValidProductData();
        $payload['name'] = $product->name;

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }
}
