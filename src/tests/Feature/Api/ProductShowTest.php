<?php

namespace Tests\Feature\Api;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductShowTest extends ApiTestCase
{
    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson($this->getApiUrl("/products/{$product->id}"));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
            ]);
    }

    public function test_returns_not_found_for_invalid_product(): void
    {
        $response = $this->getJson($this->getApiUrl('/products/999999'));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
