<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Modules\Cart\DTO\AddToCartInput;
use App\Modules\Cart\DTO\UpdateCartItemInput;
use App\Modules\Cart\Exceptions\CartLimitExceededException;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Product\Models\Product;
use Tests\Feature\Api\ApiTestCase;

class CartConcurrencyTest extends ApiTestCase
{
    public function test_sequential_adds_merge_correctly(): void
    {
        $user = $this->createUser();
        $product = Product::factory()->pizza()->create();
        $service = $this->app->make(abstract: CartService::class);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 5));
        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 3));

        $item = CartItem::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(expected: 8, actual: $item->quantity);
    }

    public function test_limit_checks_sum_of_quantity_not_count_of_items(): void
    {
        $user = $this->createUser();
        $pizza1 = Product::factory()->pizza()->create();
        $pizza2 = Product::factory()->pizza()->create();
        $service = $this->app->make(abstract: CartService::class);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza1->id, quantity: 8));

        try {
            $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza2->id, quantity: 3));
            $this->fail('Expected CartLimitExceededException was not thrown');
        } catch (CartLimitExceededException $e) {
            $this->assertStringContainsString(needle: 'пицц', haystack: $e->getMessage());
        }

        $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza2->id, quantity: 2));

        $totalPizzaQuantity = CartItem::query()
            ->whereIn('product_id', [$pizza1->id, $pizza2->id])
            ->sum(column: 'quantity');

        $this->assertSame(expected: 10, actual: $totalPizzaQuantity);
    }

    public function test_decrease_quantity_bypasses_limit_check(): void
    {
        $user = $this->createUser();
        $pizza = Product::factory()->pizza()->create();
        $service = $this->app->make(abstract: CartService::class);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza->id, quantity: 10));

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $item = CartItem::where('cart_id', $cart->id)->firstOrFail();

        $service->updateItem(user: $user, itemId: $item->id, input: new UpdateCartItemInput(quantity: 5));

        $item->refresh();
        $this->assertSame(expected: 5, actual: $item->quantity);
    }

    public function test_pizza_and_drink_limits_are_independent(): void
    {
        $user = $this->createUser();
        $pizza = Product::factory()->pizza()->create();
        $drink = Product::factory()->drink()->create();
        $service = $this->app->make(abstract: CartService::class);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza->id, quantity: 10));
        $service->addItem(user: $user, input: new AddToCartInput(productId: $drink->id, quantity: 20));

        $totalQuantity = CartItem::query()
            ->where(column: 'cart_id', operator: Cart::where('user_id', $user->id)->firstOrFail()->id)
            ->sum('quantity');

        $this->assertSame(expected: 30, actual: $totalQuantity);
    }
}
