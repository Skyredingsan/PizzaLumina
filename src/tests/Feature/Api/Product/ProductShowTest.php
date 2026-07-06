<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class ProductShowTest extends ApiTestCase
{
    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson($this->getApiUrl("/products/{$product->id}"));

        $response->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.id', expect: $product->id)
            ->assertJsonPath(path: 'data.name', expect: $product->name)
            ->assertJsonPath(path: 'data.price.amount', expect: $product->price->getAmount())
            ->assertJsonPath(path: 'data.price.currency', expect: 'RUB');
    }

    public function test_returns_not_found_for_invalid_product(): void
    {
        $response = $this->getJson($this->getApiUrl('/products/999999'));

        $response->assertStatus(status: Response::HTTP_NOT_FOUND);
    }
}
