<?php

declare(strict_types=1);

namespace App\Modules\Product\Contracts;

use App\Modules\Product\Models\Product;

interface ProductRepositoryInterface
{
    /**
     * @return array<string, mixed> Formatted pagination response {data, links, meta}
     */
    public function findPaginated(int $page, int $perPage): array;

    /**
     * @return array<string, mixed>|null Formatted product data, or null if not found
     */
    public function findById(int $id): ?array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;
}
