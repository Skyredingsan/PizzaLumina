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

        return response()->json(
            ProductResource::collection($products)->response()->getData(true)
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json(
            new ProductResource($product),
            Response::HTTP_CREATED,
        );
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        return response()->json(new ProductResource($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->noContent();
    }
}
