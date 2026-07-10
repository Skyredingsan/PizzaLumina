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
        Product::factory()->count(count: 3)->create();

        $response = $this->getJson($this->getApiUrl('/products'));

        $response->assertStatus(status: Response::HTTP_OK)
            ->assertJsonStructure(structure: [
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
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonCount(count: 0, key: 'data')
            ->assertJsonPath(path: 'meta.total', expect: 0);
    }

    public function test_index_paginates_results_with_15_per_page(): void
    {
        Product::factory()->count(count: 20)->create();

        $this->getJson($this->getApiUrl('/products'))
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonCount(count: 15, key: 'data')              // 15 на текущей странице
            ->assertJsonPath(path: 'meta.total', expect: 20)          // всего 20
            ->assertJsonPath(path: 'meta.current_page', expect: 1)    // текущая — 1
            ->assertJsonPath(path: 'meta.last_page', expect: 2)       // всего 2 страницы
            ->assertJsonPath(path: 'meta.per_page', expect: 15)
            ->assertJsonPath(path: 'meta.from', expect: 1)
            ->assertJsonPath(path: 'meta.to', expect: 15);
    }

    public function test_index_can_access_second_page(): void
    {
        Product::factory()->count(count: 20)->create();

        $this->getJson($this->getApiUrl('/products?page=2'))
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonCount(count: 5, key: 'data')                // 20 - 15 = 5 на второй
            ->assertJsonPath(path: 'meta.current_page', expect: 2);
    }
}
