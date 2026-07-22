<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\ProductCacheService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly ProductCacheService $cacheService,
    ) {
    }

    public function index(): JsonResponse
    {
        $page = (int) request()->input(key: 'page', default: 1);
        $perPage = (int) request()->input(key: 'per_page', default: self::PER_PAGE);

        $data = $this->cacheService->rememberList(page: $page, perPage: $perPage, loader: function () use ($page, $perPage): array {
            $paginator = Product::query()->paginate(perPage: $perPage, columns: ['*'], page: $page);
            $arr = $paginator->toArray();

            return [
                'data' => $arr['data'],
                'links' => [
                    'first' => $arr['first_page_url'] ?? null,
                    'last' => $arr['last_page_url'] ?? null,
                    'prev' => $arr['prev_page_url'] ?? null,
                    'next' => $arr['next_page_url'] ?? null,
                ],
                'meta' => [
                    'current_page' => $arr['current_page'],
                    'last_page' => $arr['last_page'],
                    'per_page' => $arr['per_page'],
                    'total' => $arr['total'],
                    'from' => $arr['from'],
                    'to' => $arr['to'],
                ],
            ];
        });

        return response()->json($data);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource(resource: $product))
            ->response()
            ->setStatusCode(code: Response::HTTP_CREATED);
    }

    public function show(Product $product): JsonResponse
    {
        $data = $this->cacheService->rememberProduct(id: $product->id, loader: fn (): array => (new ProductResource(resource: $product))->resolve());

        return response()->json(['data' => $data]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update(attributes: $request->validated());

        return (new ProductResource(resource: $product))->response();
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
