<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Modules\Cart\Models\CartItem;
use App\Modules\Product\Models\Product;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class CartManagementTest extends ApiTestCase
{
    public function test_add_item_creates_cart_if_not_exists(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])->assertStatus(status: Response::HTTP_CREATED)
            ->assertJsonPath(path: 'data.items_count', expect: 1)
            ->assertJsonPath(path: 'data.total_quantity', expect: 2);

        $this->assertDatabaseHas('carts', ['user_id' => $this->lastUser()->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_add_item_merges_quantity_if_product_already_in_cart(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])->assertCreated();

        $this->withToken($this->lastToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 3,
            ])->assertCreated()
            ->assertJsonPath(path: 'data.items_count', expect: 1)
            ->assertJsonPath(path: 'data.total_quantity', expect: 5);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_add_item_without_quantity_defaults_to_one(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
            ])->assertCreated()
            ->assertJsonPath(path: 'data.total_quantity', expect: 1);
    }

    public function test_add_item_with_nonexistent_product_returns_422(): void
    {
        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => 999999,
                'quantity' => 1,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['product_id']);
    }

    public function test_add_item_with_zero_quantity_returns_422(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 0,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: ['quantity']);
    }

    public function test_update_item_quantity(): void
    {
        $product = Product::factory()->create();
        $token = $this->customerToken();
        $this->lastUser();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertCreated();

        $item = CartItem::where('product_id', $product->id)->firstOrFail();

        $this->withToken($token)
            ->patchJson($this->getApiUrl("/cart/items/{$item->id}"), [
                'quantity' => 5,
            ])->assertOk()
            ->assertJsonPath(path: 'data.total_quantity', expect: 5);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_update_nonexistent_item_returns_404(): void
    {
        $this->withToken($this->customerToken())
            ->patchJson($this->getApiUrl('/cart/items/999999'), [
                'quantity' => 5,
            ])->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_remove_item_from_cart(): void
    {
        $product = Product::factory()->create();
        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertCreated();

        $item = CartItem::where('product_id', $product->id)->firstOrFail();

        $this->withToken($token)
            ->deleteJson($this->getApiUrl("/cart/items/{$item->id}"))
            ->assertOk()
            ->assertJsonPath(path: 'data.items_count', expect: 0);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_remove_nonexistent_item_returns_404(): void
    {
        $this->withToken($this->customerToken())
            ->deleteJson($this->getApiUrl('/cart/items/999999'))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_clear_cart(): void
    {
        $token = $this->customerToken();

        Product::factory()->count(count: 3)->create()->each(function (Product $product) use ($token): void {
            $this->withToken($token)
                ->postJson($this->getApiUrl('/cart/items'), [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ])->assertCreated();
        });

        $this->withToken($token)
            ->deleteJson($this->getApiUrl('/cart'))
            ->assertOk()
            ->assertJsonPath(path: 'data.items_count', expect: 0);

        $this->assertDatabaseCount(table: 'cart_items', count: 0);
    }

    public function test_carts_are_isolated_between_users(): void
    {
        $userA = $this->createUser(UserRole::Customer);
        $userB = $this->createUser(UserRole::Customer);
        $tokenA = $this->getTokenForUser($userA);
        $tokenB = $this->getTokenForUser($userB);

        $product = Product::factory()->create();

        $this->withToken($tokenA)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertCreated();

        $this->withToken($tokenA)
            ->getJson($this->getApiUrl('/cart'))
            ->assertOk()
            ->assertJsonPath(path: 'data.items_count', expect: 1);

        $this->withToken($tokenB)
            ->getJson($this->getApiUrl('/cart'))
            ->assertOk()
            ->assertJsonPath(path: 'data.items_count', expect: 0);
    }

    public function test_cannot_exceed_max_pizzas_limit(): void
    {
        $product = Product::factory()->pizza()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 11,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_can_add_exactly_max_pizzas(): void
    {
        $product = Product::factory()->pizza()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 10,
            ])->assertCreated();
    }

    public function test_cannot_exceed_max_drinks_limit(): void
    {
        $product = Product::factory()->drink()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 21,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_can_add_exactly_max_drinks(): void
    {
        $product = Product::factory()->drink()->create();

        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 20,
            ])->assertCreated();
    }

    public function test_pizza_and_drink_limits_are_independent(): void
    {
        $pizza = Product::factory()->pizza()->create();
        $drink = Product::factory()->drink()->create();
        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $pizza->id,
                'quantity' => 10,
            ])->assertCreated();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $drink->id,
                'quantity' => 20,
            ])->assertCreated()
            ->assertJsonPath(path: 'data.items_count', expect: 2)
            ->assertJsonPath(path: 'data.total_quantity', expect: 30);
    }

    public function test_update_quantity_cannot_exceed_limit(): void
    {
        $pizza = Product::factory()->pizza()->create();
        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $pizza->id,
                'quantity' => 5,
            ])->assertCreated();

        $item = CartItem::where('product_id', $pizza->id)->firstOrFail();

        $this->withToken($token)
            ->patchJson($this->getApiUrl("/cart/items/{$item->id}"), [
                'quantity' => 11,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->withToken($token)
            ->patchJson($this->getApiUrl("/cart/items/{$item->id}"), [
                'quantity' => 10,
            ])->assertOk();
    }

    public function test_multiple_pizzas_sum_quantity_against_limit(): void
    {
        $pizza1 = Product::factory()->pizza()->create();
        $pizza2 = Product::factory()->pizza()->create();
        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $pizza1->id,
                'quantity' => 7,
            ])->assertCreated();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $pizza2->id,
                'quantity' => 5,
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $pizza2->id,
                'quantity' => 3,
            ])->assertCreated();
    }

    private function lastUser(): User
    {
        return User::latest()->firstOrFail();
    }

    private function lastToken(): string
    {
        return $this->getTokenForUser($this->lastUser());
    }
}
