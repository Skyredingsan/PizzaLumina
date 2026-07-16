<?php

declare(strict_types=1);

namespace App\Modules\Cart\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Requests\AddCartItemRequest;
use App\Modules\Cart\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Resources\CartResource;
use App\Modules\Cart\Services\CartService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCartForUser(user: $this->user(request: $request));

        return (new CartResource(resource: $cart))
            ->response()
            ->setStatusCode(code: Response::HTTP_OK);
    }

    public function add(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem(
            user: $this->user(request: $request),
            input: $request->toAddToCartInput(),
        );

        return (new CartResource(resource: $cart))
            ->response()
            ->setStatusCode(code: Response::HTTP_CREATED);
    }

    public function update(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        $cart = $this->cartService->updateItem(
            user: $this->user(request: $request),
            itemId: $item,
            input: $request->toUpdateCartItemInput(),
        );

        return (new CartResource(resource: $cart))->response();
    }

    public function remove(Request $request, int $item): JsonResponse
    {
        $cart = $this->cartService->removeItem(user: $this->user(request: $request), itemId: $item);

        return (new CartResource(resource: $cart))->response();
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clearCart(user: $this->user(request: $request));

        return (new CartResource(resource: $cart))->response();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
