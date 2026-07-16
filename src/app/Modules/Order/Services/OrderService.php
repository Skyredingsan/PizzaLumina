<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\DTO\Address;
use App\Modules\Order\DTO\CreateOrderInput;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\EmptyCartException;
use App\Modules\Order\Exceptions\InvalidOrderTransitionException;
use App\Modules\Order\Exceptions\OrderNotFoundException;
use App\Modules\Order\Exceptions\OrderTooLargeException;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use App\Shared\ValueObjects\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class OrderService
{
    private const MAX_ORDER_ITEMS = 50;

    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * @throws EmptyCartException
     * @throws OrderTooLargeException
     * @throws InvalidOrderTransitionException
     */
    public function createOrder(User $user, CreateOrderInput $input): Order
    {
        return DB::transaction(callback: function () use ($user, $input): Order {
            $cart = $this->lockCartForOrder(user: $user);

            if ($cart->items->isEmpty()) {
                throw EmptyCartException::forUser(userId: $user->id);
            }

            if ($cart->items->count() > self::MAX_ORDER_ITEMS) {
                throw OrderTooLargeException::forCount(
                    maxItems: self::MAX_ORDER_ITEMS,
                    attempted: $cart->items->count(),
                );
            }

            $totalAmount = Money::fromCents(cents: 0);
            foreach ($cart->items as $item) {
                $totalAmount = $totalAmount->add(other: $item->product->price->multiply(factor: $item->quantity));
            }

            $orderData = [
                'user_id' => $user->id,
                'status' => OrderStatus::Created->value,
                'total_amount' => $totalAmount->getAmount(),
                'delivery_method' => $input->deliveryMethod->value,
            ];

            if ($input->address instanceof Address) {
                $orderData = array_merge($orderData, $this->addressToArray(address: $input->address));
            }

            /** @var Order $order */
            $order = Order::create($orderData);

            foreach ($cart->items as $item) {
                /** @var Product $product */
                $product = $item->product;

                $order->items()->create(attributes: [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_category' => $product->category->value,
                    'unit_price' => $product->price->getAmount(),
                    'quantity' => $item->quantity,
                ]);
            }

            $cart->items()->delete();

            return $order->load(relations: ['items', 'user']);
        });
    }

    public function payOrder(Order $order): Order
    {
        return $this->transitionTo(order: $order, status: OrderStatus::Paid);
    }

    public function cancelOrder(Order $order): Order
    {
        return $this->transitionTo(order: $order, status: OrderStatus::Cancelled);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        return $this->transitionTo(order: $order, status: $status);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function listOrdersForUser(User $user): LengthAwarePaginator
    {
        return Order::where(column: 'user_id', operator: $user->id)
            ->with(relations: ['items'])
            ->latest()
            ->paginate(perPage: 15);
    }

    public function getOrderForUser(User $user, int $orderId): Order
    {
        /** @var Order|null $order */
        $order = Order::with(relations: ['items'])
            ->where(column: 'user_id', operator: $user->id)
            ->find(id: $orderId);

        if ($order === null) {
            throw OrderNotFoundException::forOrder(orderId: $orderId);
        }

        return $order;
    }

    public function getOrder(int $orderId): Order
    {
        /** @var Order|null $order */
        $order = Order::with(relations: ['items', 'user'])->find(id: $orderId);

        if ($order === null) {
            throw OrderNotFoundException::forOrder(orderId: $orderId);
        }

        return $order;
    }

    private function lockCartForOrder(User $user): Cart
    {
        $cart = $this->cartService->lockCartForUser(user: $user);

        return $cart->load(relations: ['items.product']);
    }

    private function transitionTo(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(callback: function () use ($order, $status): Order {
            // Блокируем заказ на время перехода
            /** @var Order $locked */
            $locked = Order::where(column: 'id', operator: $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = $locked->status;

            // Defensive: статус из БД может прийти как строка
            if (!$current instanceof OrderStatus) {
                $current = OrderStatus::from(value: $current);
            }

            if (!$current->canTransitionTo(next: $status)) {
                throw InvalidOrderTransitionException::forTransition(from: $current, to: $status);
            }

            $updateData = ['status' => $status->value];

            // Обновляем соответствующий timestamp
            $now = now();
            match ($status) {
                OrderStatus::Paid => $updateData['paid_at'] = $now,
                OrderStatus::Completed => $updateData['completed_at'] = $now,
                OrderStatus::Cancelled => $updateData['cancelled_at'] = $now,
                default => null,
            };

            $locked->update(attributes: $updateData);

            return $locked->fresh();
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function addressToArray(Address $address): array
    {
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
}
