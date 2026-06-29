<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

/**
 * Тесты для PATCH /api/products/{id} (частичное обновление).
 */
class ProductUpdateTest extends ApiTestCase
{
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();
        $updatedData = ['name' => 'Updated Pizza', 'price' => '2500'];

        $response = $this->patchJson(
            $this->getApiUrl("/products/{$product->id}"),
            $updatedData
        );

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'name'  => 'Updated Pizza',
                'price' => [
                    'amount'   => 250000,   // 2500 рублей = 250000 центов
                    'rubles'   => 2500.0,
                    'currency' => 'RUB',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'Updated Pizza',
            'price' => 250000,
        ]);
    }

    public function test_can_partially_update_product(): void
    {
        $product = Product::factory()->create([
            'name'  => 'Original',
            'price' => 100000,  // 1000.00 руб в центах
        ]);

        $this->patchJson(
            $this->getApiUrl("/products/{$product->id}"),
            ['name' => 'Updated Only Name']
        )->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment(['name' => 'Updated Only Name']);

        // Цена не должна была измениться
        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'price' => 100000,
        ]);
    }

    public function test_cannot_update_product_with_invalid_price(): void
    {
        $product = Product::factory()->create();

        $this->patchJson(
            $this->getApiUrl("/products/{$product->id}"),
            ['price' => 'invalid']
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_cannot_update_product_with_negative_price(): void
    {
        $product = Product::factory()->create();

        $this->patchJson(
            $this->getApiUrl("/products/{$product->id}"),
            ['price' => -100]
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_cannot_update_nonexistent_product(): void
    {
        $this->patchJson(
            $this->getApiUrl('/products/999999'),
            ['name' => 'Whatever']
        )->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
