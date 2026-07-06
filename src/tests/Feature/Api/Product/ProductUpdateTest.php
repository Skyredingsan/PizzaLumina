<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class ProductUpdateTest extends ApiTestCase
{
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();

        $updatedData = [
            'name' => 'Updated Pizza',
            'description' => 'Updated description',
            'price' => '2500',
            'weight' => 500,
            'category' => $product->category->value,
        ];

        $response = $this->withToken($this->adminToken())
            ->patchJson($this->getApiUrl("/products/{$product->id}"), $updatedData);

        $response->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.name', expect: 'Updated Pizza')
            ->assertJsonPath(path: 'data.price.amount', expect: 250000);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Pizza',
            'price' => 250000,
        ]);
    }

    public function test_can_partially_update_product(): void
    {
        $product = Product::factory()->create(attributes: [
            'name' => 'Original Name',
            'price' => 150000,  // 1500.00 руб
        ]);

        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['name' => 'Updated Only Name']
            )
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.name', expect: 'Updated Only Name');

        // Цена не должна была измениться
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 150000,
        ]);
    }

    public function test_cannot_update_product_with_invalid_price(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['price' => 'invalid']
            )
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['price']);
    }

    public function test_cannot_update_product_with_negative_price(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['price' => -100]
            )
            ->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['price']);
    }

    public function test_cannot_update_nonexistent_product(): void
    {
        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl('/products/999999'),
                ['name' => 'Whatever']
            )
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }
}
