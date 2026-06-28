<?php

namespace Tests\Feature\Api\Product;

use Tests\Feature\Api\ApiTestCase;
use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductDestroyTest extends ApiTestCase
{
    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson($this->getApiUrl("/products/{$product->id}"));

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_returns_not_found_when_deleting_invalid_product(): void
    {
        $response = $this->deleteJson($this->getApiUrl('/products/999999'));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
