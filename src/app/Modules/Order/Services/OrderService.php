<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\DTO\Address;
use App\Modules\Order\DTO\CreateOrderInput;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Exceptions\EmptyCartException;
use App\Modules\Order\Exceptions\InvalidOrderTransitionException;
use App\Modules\Order\Exceptions\OrderNotFoundException;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @see OrderStatus::canTransitionTo()
 */
final readonly class OrderService
{
    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * @throws EmptyCartException Если корзина пуста.
     */
    public function createOrder(User $user, CreateOrderInput $input): Order
    {
        return DB::transaction(callback: function () use ($user, $input): Order {
            $cart = $this->cartService->getOrCreateCart(user: $user);

            /** @var Cart $locked */
            $locked = Cart::lockForUpdate()->findOrFail(id: $cart->id);

            /** @var Collection<int, CartItem> $items */
            $items = $locked->items()->with(relations: 'product')->get();

            $total = 0.0;
            $orderItemsData = [];

            foreach ($items as $item) {
                $product = $item->product;

                if (! $product instanceof Product) {
                    continue;
                }

                $priceRubles = $product->price->getRubles();
                $lineTotal = $priceRubles * $item->quantity;
                $total += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $product->name,
                    'product_category' => $product->category->value,
                    'product_price' => number_format(num: $priceRubles, decimals: 2, decimal_separator: '.', thousands_separator: ''),
                    'quantity' => $item->quantity,
                    'line_total' => number_format(num: $lineTotal, decimals: 2, decimal_separator: '.', thousands_separator: ''),
                ];
            }

            if ($orderItemsData === []) {
                throw new EmptyCartException();
            }

            $order = Order::create(attributes: [
                'uuid' => Str::uuid()->toString(),
                'user_id' => $user->id,
                'status' => OrderStatus::Created,
                'total_amount' => number_format(num: $total, decimals: 2, decimal_separator: '.', thousands_separator: ''),
                'currency' => 'RUB',
                ...$this->addressToArray(address: $input->address),
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create(attributes: $itemData);
            }

            $locked->items()->delete();
            $order->load(relations: 'items');

            return $order;
        });
    }

    /**
     * @throws InvalidOrderTransitionException Если переход недопустим.
     */
    public function payOrder(Order $order): Order
    {
        return $this->transitionTo(order: $order, next: OrderStatus::Paid);
    }

    /**
     * @throws InvalidOrderTransitionException Если заказ в терминальном статусе.
     */
    public function cancelOrder(Order $order): Order
    {
        return $this->transitionTo(order: $order, next: OrderStatus::Cancelled);
    }

    /**
     * @throws InvalidOrderTransitionException Если переход недопустим.
     */
    public function updateStatus(Order $order, OrderStatus $next): Order
    {
        return $this->transitionTo(order: $order, next: $next);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function listOrdersForUser(User $user): LengthAwarePaginator
    {
        return Order::where(column: 'user_id', operator: $user->id)
            ->orderBy(column: 'created_at', direction: 'desc')
            ->paginate(perPage: 15);
    }

    /**
     * @throws OrderNotFoundException Если заказ не найден или чужой.
     */
    public function getOrderForUser(User $user, int $orderId): Order
    {
        $order = Order::with(relations: 'items')
            ->where(column: 'user_id', operator: $user->id)
            ->find(id: $orderId);

        if (! $order instanceof Order) {
            throw OrderNotFoundException::forOrder(orderId: $orderId);
        }

        return $order;
    }

    /**
     * @throws OrderNotFoundException
     */
    public function getOrder(int $orderId): Order
    {
        $order = Order::with(relations: 'items')->find(id: $orderId);

        if (! $order instanceof Order) {
            throw OrderNotFoundException::forOrder(orderId: $orderId);
        }

        return $order;
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    private function transitionTo(Order $order, OrderStatus $next): Order
    {
        return DB::transaction(callback: function () use ($order, $next): Order {
            /** @var Order $locked */
            $locked = Order::lockForUpdate()->findOrFail(id: $order->id);

            $currentStatus = $locked->status instanceof OrderStatus
                ? $locked->status
                : OrderStatus::from(value: (string) $locked->status);

            if (! $currentStatus->canTransitionTo(next: $next)) {
                throw InvalidOrderTransitionException::forTransition(
                    from: $currentStatus,
                    to: $next,
                );
            }

            $updates = ['status' => $next];

            $timestampField = match ($next) {
                OrderStatus::Paid => 'paid_at',
                OrderStatus::Completed => 'completed_at',
                OrderStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            if ($timestampField !== null) {
                $updates[$timestampField] = now();
            }

            $locked->update(attributes: $updates);

            return $locked;
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
