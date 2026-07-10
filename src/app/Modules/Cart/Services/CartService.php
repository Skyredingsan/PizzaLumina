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
    public const MAX_PIZZAS = 10;

    public const MAX_DRINKS = 20;

    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(attributes: ['user_id' => $user->id]);
    }

    public function getCartForUser(User $user): Cart
    {
        $cart = $this->getOrCreateCart(user: $user);
        $cart->load(relations: ['items.product']);

        return $cart;
    }

    /**
     * @throws CartLimitExceededException
     */
    public function addItem(User $user, AddToCartInput $input): Cart
    {
        return DB::transaction(callback: function () use ($user, $input): Cart {
            $cart = $this->lockCart(user: $user);
            $product = Product::findOrFail($input->productId);

            /** @var Collection<int, CartItem> $items */
            $items = $cart->items()->with('product')->get();

            $this->assertWithinLimits(
                category: $product->category,
                currentItems: $items,
                delta: $input->quantity,
            );

            $existing = $items->firstWhere(key: 'product_id', operator: $input->productId);

            if ($existing instanceof CartItem) {
                $existing->increment(column: 'quantity', amount: $input->quantity);
            } else {
                $cart->items()->create(attributes: [
                    'product_id' => $input->productId,
                    'quantity' => $input->quantity,
                ]);
            }

            $cart->load(relations: ['items.product']);

            return $cart;
        });
    }

    /**
     * @throws CartItemNotFoundException Если элемент не найден в корзине.
     * @throws CartLimitExceededException Если новое количество превышает лимит.
     */
    public function updateItem(User $user, int $itemId, UpdateCartItemInput $input): Cart
    {
        return DB::transaction(callback: function () use ($user, $itemId, $input): Cart {
            $cart = $this->lockCart(user: $user);

            /** @var Collection<int, CartItem> $items */
            $items = $cart->items()->with('product')->get();

            $item = $items->firstWhere(key: 'id', operator: $itemId);

            if (! $item instanceof CartItem) {
                throw CartItemNotFoundException::forItem(itemId: $itemId);
            }

            $product = $item->product;
            $delta = $input->quantity - $item->quantity;

            if ($delta > 0 && $product instanceof Product) {
                $this->assertWithinLimits(
                    category: $product->category,
                    currentItems: $items,
                    delta: $delta,
                );
            }

            $item->update(attributes: ['quantity' => $input->quantity]);

            $cart->load(relations: ['items.product']);

            return $cart;
        });
    }

    /**
     * @throws CartItemNotFoundException Если элемент не найден.
     */
    public function removeItem(User $user, int $itemId): Cart
    {
        return DB::transaction(callback: function () use ($user, $itemId): Cart {
            $cart = $this->lockCart(user: $user);

            $deleted = $cart->items()->where('id', $itemId)->delete();

            if ($deleted === 0) {
                throw CartItemNotFoundException::forItem(itemId: $itemId);
            }

            $cart->load(relations: ['items.product']);

            return $cart;
        });
    }

    public function clearCart(User $user): Cart
    {
        return DB::transaction(callback: function () use ($user): Cart {
            $cart = $this->lockCart(user: $user);

            $cart->items()->delete();

            $cart->load(relations: ['items.product']);

            return $cart;
        });
    }

    private function lockCart(User $user): Cart
    {
        $cart = $this->getOrCreateCart(user: $user);

        /** @var Cart $locked */
        $locked = Cart::lockForUpdate()->findOrFail(id: $cart->id);

        return $locked;
    }

    /**
             * @param  Collection<int, CartItem>  $currentItems
             *
             * @throws CartLimitExceededException
             */
    private function assertWithinLimits(
        ProductCategory $category,
        Collection $currentItems,
        int $delta,
    ): void {
        $currentQuantity = (int) $currentItems
            ->filter(callback: fn (CartItem $item): bool => $item->product?->category === $category)
            ->sum(callback: fn (CartItem $item) => $item->quantity);

        $newQuantity = $currentQuantity + $delta;

        $limit = match ($category) {
            ProductCategory::Pizza => self::MAX_PIZZAS,
            ProductCategory::Drink => self::MAX_DRINKS,
        };

        if ($newQuantity > $limit) {
            throw CartLimitExceededException::forCategory(
                category: $category,
                limit: $limit,
                attempted: $newQuantity,
            );
        }
    }
}
