<?php

namespace Tests\Feature\Api;

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
                'price' => $payload['price'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $payload['name'],
            'price' => $payload['price'] * 100,
        ]);
    }

    public function test_cannot_create_product_without_name(): void
    {
        $payload = $this->getValidProductData();
        unset($payload['name']);

        $response = $this->postJson($this->getApiUrl('/products'), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }
}
