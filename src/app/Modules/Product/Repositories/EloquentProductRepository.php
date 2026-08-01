<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Contracts\ProductRepositoryInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Resources\ProductResource;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findPaginated(int $page, int $perPage): array
    {
        $paginator = Product::query()->paginate(perPage: $perPage, columns: ['*'], page: $page);
        $arr = $paginator->toArray();

        // Format each product through ProductResource for consistency with findById()
        $data = $paginator->getCollection()
            ->map(callback: fn (Product $product): array => (new ProductResource(resource: $product))->resolve())
            ->values()
            ->toArray();

        return [
            'data' => $data,
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
    }

    public function findById(int $id): ?array
    {
        $product = Product::find($id);

        if ($product === null) {
            return null;
        }

        return (new ProductResource(resource: $product))->resolve();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
