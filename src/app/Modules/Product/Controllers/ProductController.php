<?php

declare(strict_types=1);

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Contracts\ProductRepositoryInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductResource;
use App\Shared\Requests\PaginationRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {
    }

    public function index(PaginationRequest $request): JsonResponse
    {
        $page = $request->getPage();
        $perPage = $request->getPerPage();

        $data = $this->repository->findPaginated($page, $perPage);

        return response()->json($data);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->repository->create($request->validated());

        return (new ProductResource(resource: $product))
            ->response()
            ->setStatusCode(code: Response::HTTP_CREATED);
    }

    public function show(int $product): JsonResponse
    {
        $data = $this->repository->findById($product);

        if ($data === null) {
            abort(code: 404);
        }

        return response()->json(['data' => $data]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->repository->update($product, $request->validated());

        return (new ProductResource(resource: $product))->response();
    }

    public function destroy(Product $product): Response
    {
        $this->repository->delete($product);

        return response()->noContent();
    }
}
