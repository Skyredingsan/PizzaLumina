<?php

namespace Tests\Feature\Api;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class ProductIndexTest extends ApiTestCase
{
    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson($this->getApiUrl('/products'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'price', 'category'],
            ]);
    }
}
