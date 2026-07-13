<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\DTO\Address;
use App\Modules\Order\DTO\CreateOrderInput;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\CartInvalidException;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Exceptions\OrderTooLargeException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\User\Models\User;
use App\Shared\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class OrderService
{
    private const MAX_ORDER_ITEMS = 50;

    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * @throws CartInvalidException|OrderTooLargeException|Throwable
     */
    public function createOrder(User $user, CreateOrderInput $input): Order
    {
        if ($input->deliveryMethod->requiresAddress() && !$input->address instanceof Address) {
            throw new CartInvalidException(message: (string) trans(key: 'order.address_required_for_courier'));
        }

        return DB::transaction(function () use ($user, $input): Order {
            $cart = $this->cartService->lockCartForUser(user: $user);
            $this->assertCartIsValid(cart: $cart);
            $this->assertOrderSizeIsAcceptable(cart: $cart);

            $total = Money::fromCents(cents: 0);
            foreach ($cart->items as $cartItem) {
                $line = $cartItem->product->price->multiply(factor: $cartItem->quantity);
                $total = $total->add(other: $line);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::Created,
                'total_amount' => $total->getAmount(),
                'delivery_method' => $input->deliveryMethod,
                ...$this->addressToArray(address: $input->address),
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_category' => $cartItem->product->category,
                    'unit_price' => $cartItem->product->price->getAmount(),
                    'quantity' => $cartItem->quantity,
                ]);
            }

            $cart->items()->delete();
            $cart->delete();

            return $order->fresh(['items']);
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function addressToArray(?Address $address): array
    {
        if (!$address instanceof Address) {
            return [
                'address_region' => null,
                'address_city' => null,
                'address_street' => null,
                'address_building' => null,
                'address_entrance' => null,
                'address_apartment' => null,
                'address_zip' => null,
            ];
        }

        return [
            'address_region' => $address->region,
            'address_city' => $address->city,
            'address_street' => $address->street,
            'address_building' => $address->building,
            'address_entrance' => $address->entrance,
            'address_apartment' => $address->apartment,
            'address_zip' => $address->zip,
        ];
    }

    private function assertCartIsValid(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw new CartInvalidException(message: (string) trans(key: 'order.cart_empty'));
        }
    }

    private function assertOrderSizeIsAcceptable(Cart $cart): void
    {
        if ($cart->items->count() > self::MAX_ORDER_ITEMS) {
            throw new OrderTooLargeException(message: (string) trans(key: 'order.too_large'));
        }
    }

    public function getOrderForUser(User $user, int $orderId): Order
    {
        return Order::where('user_id', $user->id)
            ->with('items')
            ->findOrFail($orderId);
    }

    public function getOrder(int $orderId): Order
    {
        return Order::with(relations: 'items')->findOrFail(id: $orderId);
    }

    /**
     * @return Collection<int, Order>
     */
    public function listOrdersForUser(User $user): Collection
    {
        return Order::where('user_id', $user->id)
            ->with('items')
            ->latest()
            ->get();
    }

    public function payOrder(Order $order): Order
    {
        if (! $order->status->canTransitionTo(next: OrderStatus::Paid)) {
            throw new InvalidStatusTransitionException(message: (string) trans(key: 'order.invalid_transition'));
        }

        $order->update(attributes: [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        return $order->fresh(with: ['items']);
    }

    public function cancelOrder(Order $order): Order
    {
        if (! $order->status->canTransitionTo(next: OrderStatus::Cancelled)) {
            throw new InvalidStatusTransitionException(message: (string) trans(key: 'order.invalid_transition'));
        }

        $order->update(attributes: [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $order->fresh(with: ['items']);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if (! $order->status->canTransitionTo(next: $status)) {
            throw new InvalidStatusTransitionException(message: (string) trans(key: 'order.invalid_transition'));
        }

        $order->update(attributes: ['status' => $status]);

        return $order->fresh(with: ['items']);
    }
}
