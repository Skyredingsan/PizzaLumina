<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Order;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use App\Shared\ValueObjects\Money;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Api\ApiTestCase;

class OrderManagementTest extends ApiTestCase
{
    public function test_create_order_from_cart_with_snapshot(): void
    {
        $product = Product::factory()->create(attributes: [
            'name' => 'Маргарита',
            'price' => Money::fromRubles(rubles: 1000),
        ]);

        $token = $this->customerToken();

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 3,
            ])->assertCreated();

        $response = $this->withToken($token)
            ->postJson($this->getApiUrl('/orders'), [
                'address' => $this->validAddress(),
            ]);

        $response->assertStatus(status: Response::HTTP_CREATED)
            ->assertJsonPath(path: 'data.status', expect: OrderStatus::Created->value)
            ->assertJsonPath(path: 'data.total_amount', expect: 3000)
            ->assertJsonPath(path: 'data.address.city', expect: 'Москва')
            ->assertJsonCount(count: 1, key: 'data.items');

        $orderId = $response->json(key: 'data.id');

        $item = OrderItem::where('order_id', $orderId)->firstOrFail();
        $this->assertSame(expected: 'Маргарита', actual: $item->product_name);
        $this->assertSame(expected: '1000.00', actual: $item->product_price);
        $this->assertSame(expected: '3000.00', actual: $item->line_total);
        $this->assertSame(expected: 3, actual: $item->quantity);

        $this->assertDatabaseCount(table: 'cart_items', count: 0);
    }

    public function test_create_order_from_empty_cart_returns_422(): void
    {
        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/orders'), [
                'address' => $this->validAddress(),
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath(path: 'message', expect: 'Невозможно оформить заказ: корзина пуста.');
    }

    public function test_create_order_validates_address(): void
    {
        $this->withToken($this->customerToken())
            ->postJson($this->getApiUrl('/orders'), [
                'address' => [
                    'city' => 'Москва',
                ],
            ])->assertStatus(status: Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(errors: [
                'address.region',
                'address.street',
                'address.building',
            ]);
    }

    public function test_list_user_orders(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);

        $this->createOrderForUser($user);
        $this->createOrderForUser($user);

        $this->withToken($token)
            ->getJson($this->getApiUrl('/orders'))
            ->assertOk()
            ->assertJsonCount(count: 2, key: 'data');
    }

    public function test_show_order_details(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);
        $order = $this->createOrderForUser($user);

        $this->withToken($token)
            ->getJson($this->getApiUrl("/orders/{$order->id}"))
            ->assertOk()
            ->assertJsonPath(path: 'data.id', expect: $order->id);
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $otherUser = $this->createUser();
        $otherOrder = $this->createOrderForUser($otherUser);

        $this->withToken($this->customerToken())
            ->getJson($this->getApiUrl("/orders/{$otherOrder->id}"))
            ->assertStatus(status: Response::HTTP_NOT_FOUND);
    }

    public function test_pay_order_transitions_created_to_paid(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);
        $order = $this->createOrderForUser($user);

        $this->withToken($token)
            ->postJson($this->getApiUrl("/orders/{$order->id}/pay"))
            ->assertOk()
            ->assertJsonPath(path: 'data.status', expect: OrderStatus::Paid->value);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Paid->value,
        ]);
        $this->assertNotNull(actual: Order::find($order->id)->paid_at);
    }

    public function test_cancel_order_from_created_status(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);
        $order = $this->createOrderForUser($user);

        $this->withToken($token)
            ->postJson($this->getApiUrl("/orders/{$order->id}/cancel"))
            ->assertOk()
            ->assertJsonPath(path: 'data.status', expect: OrderStatus::Cancelled->value);

        $this->assertNotNull(actual: Order::find($order->id)->cancelled_at);
    }

    public function test_cancel_order_from_paid_status(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);
        $order = $this->createOrderForUser($user);

        $this->withToken($token)
            ->postJson($this->getApiUrl("/orders/{$order->id}/pay"))
            ->assertOk();

        $this->withToken($token)
            ->postJson($this->getApiUrl("/orders/{$order->id}/cancel"))
            ->assertOk()
            ->assertJsonPath(path: 'data.status', expect: OrderStatus::Cancelled->value);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $user = $this->createUser();
        $order = $this->createOrderForUser($user);

        $adminToken = $this->adminToken();

        $this->withToken($adminToken)
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Paid->value,
            ])->assertOk();

        $this->withToken($adminToken)
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::InProgress->value,
            ])->assertOk();

        $this->withToken($adminToken)
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Delivering->value,
            ])->assertOk();

        $this->withToken($adminToken)
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Completed->value,
            ])->assertOk();

        $userToken = $this->getTokenForUser($user);
        $this->withToken($userToken)
            ->postJson($this->getApiUrl("/orders/{$order->id}/cancel"))
            ->assertStatus(status: Response::HTTP_CONFLICT);
    }

    public function test_admin_can_update_order_status(): void
    {
        $user = $this->createUser();
        $order = $this->createOrderForUser($user);

        $this->withToken($this->adminToken())
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Paid->value,
            ])->assertOk()
            ->assertJsonPath(path: 'data.status', expect: OrderStatus::Paid->value);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $user = $this->createUser();
        $token = $this->getTokenForUser($user);
        $order = $this->createOrderForUser($user);

        $this->withToken($token)
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Paid->value,
            ])->assertStatus(status: Response::HTTP_FORBIDDEN);
    }

    public function test_invalid_status_transition_returns_409(): void
    {
        $user = $this->createUser();
        $order = $this->createOrderForUser($user);

        $this->withToken($this->adminToken())
            ->patchJson($this->getApiUrl("/orders/{$order->id}/status"), [
                'status' => OrderStatus::Delivering->value,
            ])->assertStatus(status: Response::HTTP_CONFLICT);
    }

    public function test_order_snapshot_preserves_product_data_after_product_changes(): void
    {
        $product = Product::factory()->create(attributes: [
            'name' => 'Оригинальная пицца',
            'price' => Money::fromRubles(rubles: 500),
        ]);

        $user = $this->createUser();
        $token = $this->getTokenForUser($user);

        $this->withToken($token)
            ->postJson($this->getApiUrl('/cart/items'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])->assertCreated();

        $response = $this->withToken($token)
            ->postJson($this->getApiUrl('/orders'), [
                'address' => $this->validAddress(),
            ])->assertCreated();

        $orderId = $response->json(key: 'data.id');

        $product->update(attributes: [
            'name' => 'Новое название',
            'price' => Money::fromRubles(rubles: 999),
        ]);

        $item = OrderItem::where('order_id', $orderId)->firstOrFail();
        $this->assertSame(expected: 'Оригинальная пицца', actual: $item->product_name);
        $this->assertSame(expected: '500.00', actual: $item->product_price);
        $this->assertSame(expected: '1000.00', actual: $item->line_total);
    }

    private function validAddress(): array
    {
        return [
            'region' => 'Москва',
            'city' => 'Москва',
            'street' => 'ул. Тверская',
            'building' => '10',
            'entrance' => '3',
            'apartment' => '42',
            'zip' => '125009',
        ];
    }

    private function createOrderForUser(User $user): Order
    {
        $product = Product::factory()->create();
        $token = $this->getTokenForUser($user);
        $authHeader = ['Authorization' => "Bearer {$token}"];

        $this->postJson(
            $this->getApiUrl('/cart/items'),
            ['product_id' => $product->id, 'quantity' => 1],
            $authHeader
        )->assertCreated();

        $response = $this->postJson(
            $this->getApiUrl('/orders'),
            ['address' => $this->validAddress()],
            $authHeader
        )->assertCreated();

        return Order::findOrFail($response->json(key: 'data.id'));
    }
}
