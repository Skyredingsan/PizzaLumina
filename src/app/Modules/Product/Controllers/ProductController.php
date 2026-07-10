<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): JsonResponse
    {
        $products = Product::paginate(self::PER_PAGE);

        return ProductResource::collection(resource: $products)->response();
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
        return (new ProductResource(resource: $product))->response();
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
