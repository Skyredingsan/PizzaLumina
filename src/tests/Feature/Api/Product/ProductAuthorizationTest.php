<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

/**
 * Тесты ролевой защиты продуктовых эндпоинтов.
 *
 * По ТЗ Stage 4:
 *   - index, show — публичные (доступны всем, даже гостю)
 *   - store, update, destroy — только admin
 *
 * Проверяем матрицу доступа для каждого защищённого эндпоинта:
 *   - без токена → 401 Unauthorized
 *   - с токеном customer → 403 Forbidden
 *   - с токеном admin → 2xx (success)
 */
class ProductAuthorizationTest extends ApiTestCase
{
    public function test_guest_cannot_create_product(): void
    {
        $this->postJson($this->getApiUrl('/products'), $this->getValidProductData())
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_customer_cannot_create_product(): void
    {
        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/products'), $this->getValidProductData())
            ->assertStatus(status: Response::HTTP_FORBIDDEN);
    }

    public function test_admin_can_create_product(): void
    {
        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $this->getValidProductData())
            ->assertStatus(status: Response::HTTP_CREATED);
    }

    public function test_guest_cannot_update_product(): void
    {
        $product = Product::factory()->create();

        $this->patchJson(
            $this->getApiUrl("/products/{$product->id}"),
            ['name' => 'Hacked']
        )->assertStatus(status: Response::HTTP_UNAUTHORIZED);

        // Убедимся, что продукт не изменился
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
        ]);
    }

    public function test_customer_cannot_update_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['name' => 'Hacked']
            )
            ->assertStatus(status: Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
        ]);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['name' => 'Updated Name']
            )
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.name', expect: 'Updated Name');
    }

    public function test_guest_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson($this->getApiUrl("/products/{$product->id}"))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_customer_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->deleteJson($this->getApiUrl("/products/{$product->id}"))
            ->assertStatus(status: Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->deleteJson($this->getApiUrl("/products/{$product->id}"))
            ->assertStatus(status: Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_guest_can_list_products(): void
    {
        Product::factory()->count(count: 3)->create();

        $this->getJson($this->getApiUrl('/products'))
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonCount(count: 3, key: 'data');
    }

    public function test_guest_can_show_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson($this->getApiUrl("/products/{$product->id}"))
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.id', expect: $product->id);
    }
}
