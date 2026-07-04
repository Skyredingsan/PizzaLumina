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

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.price.amount', $product->price->getAmount())
            ->assertJsonPath('data.price.currency', 'RUB');
    }

    public function test_returns_not_found_for_invalid_product(): void
    {
        $response = $this->getJson($this->getApiUrl('/products/999999'));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
