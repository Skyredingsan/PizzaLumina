<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class ProductDestroyTest extends ApiTestCase
{
    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->deleteJson($this->getApiUrl("/products/{$product->id}"))
            ->assertStatus(status: Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_returns_not_found_when_deleting_invalid_product(): void
    {
        $this->withToken($this->adminToken())
            ->deleteJson($this->getApiUrl('/products/999999'))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }
}
