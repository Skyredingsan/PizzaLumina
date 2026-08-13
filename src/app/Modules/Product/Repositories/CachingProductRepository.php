<?php

declare(strict_types=1);

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Contracts\ProductRepositoryInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Services\ProductCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CachingProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private ProductRepositoryInterface $inner,
        private ProductCacheService $cacheService,
        private int $listTtl = 3600,
        private int $itemTtl = 3600,
    ) {
    }

    public function findPaginated(int $page, int $perPage): array
    {
        if (! $this->cacheService->supportsTags() || $this->listTtl <= 0) {
            return $this->inner->findPaginated($page, $perPage);
        }

        $key = $this->cacheService->listKey(page: $page, perPage: $perPage);

        try {
            $cached = Cache::tags([ProductCacheService::TAG])->get(key: $key);
            if (is_array(value: $cached)) {
                return $cached;
            }

            $value = $this->inner->findPaginated($page, $perPage);
            Cache::tags([ProductCacheService::TAG])->put(key: $key, value: $value, ttl: $this->listTtl);

            return $value;
        } catch (Throwable $e) {
            Log::warning('CachingProductRepository list cache failed', ['exception' => $e->getMessage()]);

            return $this->inner->findPaginated($page, $perPage);
        }
    }

    public function findById(int $id): ?array
    {
        if (! $this->cacheService->supportsTags() || $this->itemTtl <= 0) {
            return $this->inner->findById($id);
        }

        $key = $this->cacheService->productKey(id: $id);

        try {
            $cached = Cache::tags([ProductCacheService::TAG])->get(key: $key);
            if (is_array(value: $cached)) {
                return $cached;
            }

            $value = $this->inner->findById($id);

            if ($value !== null) {
                Cache::tags([ProductCacheService::TAG])->put(key: $key, value: $value, ttl: $this->itemTtl);
            }

            return $value;
        } catch (Throwable $e) {
            Log::warning('CachingProductRepository item cache failed', ['id' => $id, 'exception' => $e->getMessage()]);

            return $this->inner->findById($id);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return $this->inner->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return $this->inner->update($product, $data);
    }

    public function delete(Product $product): void
    {
        $this->inner->delete($product);
    }
}
