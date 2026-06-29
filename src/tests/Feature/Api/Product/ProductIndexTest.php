<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

/**
 * Тесты для GET /api/products (список с пагинацией).
 */
class ProductIndexTest extends ApiTestCase
{
    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson($this->getApiUrl('/products'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'weight', 'category', 'created_at', 'updated_at'],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Тест на пустой список.
     */
    public function test_index_returns_empty_data_when_no_products(): void
    {
        $this->getJson($this->getApiUrl('/products'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_index_paginates_results_with_15_per_page(): void
    {
        Product::factory()->count(20)->create();

        $this->getJson($this->getApiUrl('/products'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(15, 'data')              // 15 на текущей странице
            ->assertJsonPath('meta.total', 20)          // всего 20
            ->assertJsonPath('meta.current_page', 1)    // текущая — 1
            ->assertJsonPath('meta.last_page', 2)       // всего 2 страницы
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.from', 1)
            ->assertJsonPath('meta.to', 15);
    }

    public function test_index_can_access_second_page(): void
    {
        Product::factory()->count(20)->create();

        $this->getJson($this->getApiUrl('/products?page=2'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(5, 'data')                // 20 - 15 = 5 на второй
            ->assertJsonPath('meta.current_page', 2);
    }
}
