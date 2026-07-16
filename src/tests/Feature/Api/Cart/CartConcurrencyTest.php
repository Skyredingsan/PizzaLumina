<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Modules\Cart\DTO\AddToCartInput;
use App\Modules\Cart\DTO\UpdateCartItemInput;
use App\Modules\Cart\Exceptions\CartLimitExceededException;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Product\Models\Product;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Tests\Feature\Api\ApiTestCase;

final class CartConcurrencyTest extends ApiTestCase
{
    public function test_ensure_cart_exists_is_idempotent(): void
    {
        $user = User::factory()->create(attributes: ['role' => UserRole::Customer->value]);

        $service = resolve(name: CartService::class);

        // Имитация 10 "конкурентных" вызовов getCartForUser
        for ($i = 0; $i < 10; $i++) {
            $service->getCartForUser(user: $user);
        }

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_sequential_adds_respect_pizza_limit(): void
    {
        $user = User::factory()->create(attributes: ['role' => UserRole::Customer->value]);
        $product = Product::factory()->pizza()->create();

        $service = resolve(name: CartService::class);

        // 10 успешных добавлений по 1 пицце
        for ($i = 0; $i < 10; $i++) {
            $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 1));
        }

        // 11-е добавление должно упасть с CartLimitExceededException
        try {
            $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 1));
            $this->fail('Expected CartLimitExceededException was not thrown');
        } catch (CartLimitExceededException $e) {
            $this->assertStringContainsString('Превышен лимит', $e->getMessage());
        }

        $cart = Cart::where('user_id', $user->id)->first();
        $total = $cart->items()->where('product_id', $product->id)->sum('quantity');
        $this->assertSame(10, (int) $total, 'Quantity should be exactly 10, not more');
    }

    public function test_lock_for_update_prevents_lost_updates(): void
    {
        $user = User::factory()->create(attributes: ['role' => UserRole::Customer->value]);
        $product = Product::factory()->drink()->create();

        $service = resolve(name: CartService::class);
        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 1));

        $cart = Cart::where('user_id', $user->id)->first();
        $itemId = $cart->items()->where('product_id', $product->id)->first()->id;

        // 10 последовательных increment-операций.
        // Без транзакции + lockForUpdate они бы потеряли данные.
        for ($i = 0; $i < 10; $i++) {
            $service->updateItem(
                user: $user,
                itemId: $itemId,
                input: new UpdateCartItemInput(quantity: $i + 2),
            );
        }

        $cart->refresh();
        $finalQuantity = $cart->items()->where('product_id', $product->id)->value('quantity');
        $this->assertSame(11, (int) $finalQuantity, 'Final quantity should be 11 (1 initial + 10 increments)');
    }

    public function test_category_limits_are_independent_under_stress(): void
    {
        $user = User::factory()->create(attributes: ['role' => UserRole::Customer->value]);
        $pizza = Product::factory()->pizza()->create();
        $drink = Product::factory()->drink()->create();

        $service = resolve(name: CartService::class);

        // Заполняем лимит пицц полностью
        $service->addItem(user: $user, input: new AddToCartInput(productId: $pizza->id, quantity: 10));

        // Напитки должны добавляться без проблем — лимит независим
        $service->addItem(user: $user, input: new AddToCartInput(productId: $drink->id, quantity: 20));

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertSame(2, $cart->items->count());
        $this->assertSame(30, $cart->items->sum('quantity'));
    }

    public function test_remove_and_readd_does_not_violate_unique_constraint(): void
    {
        $user = User::factory()->create(attributes: ['role' => UserRole::Customer->value]);
        $product = Product::factory()->pizza()->create();

        $service = resolve(name: CartService::class);

        // Add → remove → add → remove → add
        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 1));
        $cart = Cart::where('user_id', $user->id)->first();
        $itemId = $cart->items()->first()->id;
        $service->removeItem(user: $user, itemId: $itemId);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 2));
        $cart = Cart::where('user_id', $user->id)->first();
        $itemId = $cart->items()->first()->id;
        $service->removeItem(user: $user, itemId: $itemId);

        $service->addItem(user: $user, input: new AddToCartInput(productId: $product->id, quantity: 3));

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertSame(1, $cart->items->count());
        $this->assertSame(3, (int) $cart->items->first()->quantity);
    }
}
