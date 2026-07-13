<?php

declare(strict_types=1);

namespace App\Modules\Cart\Services;

use App\Modules\Cart\DTO\AddToCartInput;
use App\Modules\Cart\DTO\UpdateCartItemInput;
use App\Modules\Cart\Exceptions\CartItemNotFoundException;
use App\Modules\Cart\Exceptions\CartLimitExceededException;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Product\Enums\ProductCategory;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class CartService
{
    public function getCartForUser(User $user): Cart
    {
        $this->ensureCartExists(user: $user);

        return Cart::with(relations: ['items.product'])
            ->where(column: 'user_id', operator: $user->id)
            ->firstOrFail();
    }

    public function addItem(User $user, AddToCartInput $input): Cart
    {
        return DB::transaction(callback: function () use ($user, $input): Cart {
            $cart = $this->lockCart(user: $user);
            $product = Product::findOrFail($input->productId);

            $this->assertWithinLimits(
                category: $product->category,
                items: $cart->items,
                delta: $input->quantity,
            );

            $existing = $cart->items->firstWhere(key: 'product_id', operator: $product->id);

            if ($existing !== null) {
                $existing->increment(column: 'quantity', amount: $input->quantity);
            } else {
                $cart->items()->create(attributes: [
                    'product_id' => $product->id,
                    'quantity' => $input->quantity,
                ]);
            }

            return $this->getCartForUser(user: $user);
        });
    }

    public function updateItem(User $user, int $itemId, UpdateCartItemInput $input): Cart
    {
        return DB::transaction(callback: function () use ($user, $itemId, $input): Cart {
            $cart = $this->lockCart(user: $user);
            $item = $cart->items->firstWhere(key: 'id', operator: $itemId);

            if ($item === null) {
                throw CartItemNotFoundException::forItem(itemId: $itemId);
            }

            $delta = $input->quantity - $item->quantity;

            if ($delta > 0) {
                $this->assertWithinLimits(
                    category: $item->product->category,
                    items: $cart->items,
                    delta: $delta,
                );
            }

            $item->update(attributes: ['quantity' => $input->quantity]);

            return $this->getCartForUser(user: $user);
        });
    }

    public function removeItem(User $user, int $itemId): Cart
    {
        return DB::transaction(callback: function () use ($user, $itemId): Cart {
            $cart = $this->lockCart(user: $user);
            $item = $cart->items->firstWhere(key: 'id', operator: $itemId);

            if ($item === null) {
                throw CartItemNotFoundException::forItem(itemId: $itemId);
            }

            $item->delete();

            return $this->getCartForUser(user: $user);
        });
    }

    public function clearCart(User $user): Cart
    {
        return DB::transaction(callback: function () use ($user): Cart {
            $cart = $this->lockCart(user: $user);
            $cart->items()->delete();

            return $this->getCartForUser(user: $user);
        });
    }

    private function ensureCartExists(User $user): void
    {
        Cart::upsert(
            values: [
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            uniqueBy: ['user_id'],
            update: ['updated_at' => now()],
        );
    }

    public function lockCartForUser(User $user): Cart
    {
        return $this->lockCart(user: $user);
    }

    private function lockCart(User $user): Cart
    {
        $this->ensureCartExists(user: $user);

        return Cart::with(relations: ['items.product'])
            ->where(column: 'user_id', operator: $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function assertWithinLimits(
        ProductCategory $category,
        Collection $items,
        int $delta,
    ): void {
        $current = $items
            ->filter(callback: fn (CartItem $item): bool => $item->product?->category === $category)
            ->sum(callback: fn (CartItem $item): int => $item->quantity);

        $total = $current + $delta;
        $limit = $category->cartLimit();

        if ($total > $limit) {
            throw CartLimitExceededException::forCategory(
                category: $category,
                limit: $limit,
                attempted: $total,
            );
        }
    }
}
