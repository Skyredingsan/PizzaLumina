<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\CreateOrderRequest;
use App\Modules\Order\Requests\UpdateOrderStatusRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\OrderService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listOrdersForUser(user: $this->user(request: $request));

        return OrderResource::collection(resource: $orders)->response();
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            user: $this->user(request: $request),
            input: $request->toCreateOrderInput(),
        );

        return (new OrderResource(resource: $order))
            ->response()
            ->setStatusCode(code: Response::HTTP_CREATED);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $user = $this->user(request: $request);
        $orderModel = $user->isAdmin()
            ? $this->orderService->getOrder(orderId: $order)
            : $this->orderService->getOrderForUser(user: $user, orderId: $order);

        return (new OrderResource(resource: $orderModel))->response();
    }

    public function pay(Request $request, int $order): JsonResponse
    {
        $user = $this->user(request: $request);
        $orderModel = $user->isAdmin()
            ? $this->orderService->getOrder(orderId: $order)
            : $this->orderService->getOrderForUser(user: $user, orderId: $order);

        $paid = $this->orderService->payOrder(order: $orderModel);

        return (new OrderResource(resource: $paid))->response();
    }

    public function cancel(Request $request, int $order): JsonResponse
    {
        $user = $this->user(request: $request);
        $orderModel = $user->isAdmin()
            ? $this->orderService->getOrder(orderId: $order)
            : $this->orderService->getOrderForUser(user: $user, orderId: $order);

        $cancelled = $this->orderService->cancelOrder(order: $orderModel);

        return (new OrderResource(resource: $cancelled))->response();
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $order): JsonResponse
    {
        $orderModel = $this->orderService->getOrder(orderId: $order);

        $updated = $this->orderService->updateStatus(
            order: $orderModel,
            status: $request->toStatus(),
        );

        return (new OrderResource(resource: $updated))->response();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
