<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class CartAuthorizationTest extends ApiTestCase
{
    public function test_guest_cannot_view_cart(): void
    {
        $this->getJson($this->getApiUrl('/cart'))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_guest_cannot_add_item_to_cart(): void
    {
        $product = Product::factory()->create();

        $this->postJson($this->getApiUrl('/cart/items'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_guest_cannot_clear_cart(): void
    {
        $this->deleteJson($this->getApiUrl('/cart'))
            ->assertStatus(status: Response::HTTP_UNAUTHORIZED);
    }

    public function test_customer_can_view_empty_cart(): void
    {
        $this->withToken($this->customerToken())
            ->getJson($this->getApiUrl('/cart'))
            ->assertStatus(status: Response::HTTP_OK)
            ->assertJsonPath(path: 'data.items_count', expect: 0);
    }

    public function test_admin_can_use_cart(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])->assertStatus(status: Response::HTTP_CREATED);
    }
}
