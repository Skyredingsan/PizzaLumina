<?php

namespace Tests\Feature\Api;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductUpdateTest extends ApiTestCase
{
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();
        $updatedData = ['name' => 'Updated Pizza', 'price' => 2500];

        $response = $this->patchJson($this->getApiUrl("/products/{$product->id}"), $updatedData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment($updatedData);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Pizza',
            'price' => 2500 * 100,
        ]);
    }

    public function test_cannot_update_product_with_invalid_price(): void
    {
        $product = Product::factory()->create();
        $payload = ['price' => 'invalid'];

        $response = $this->patchJson($this->getApiUrl("/products/{$product->id}"), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['price']);
    }
}
