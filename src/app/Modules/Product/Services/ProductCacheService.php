<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache utilities for products: tag constant, key generators, invalidation.
 *
 * Cache read/write logic lives in CachingProductRepository (decorator).
 * This class is used by ProductObserver to invalidate cache on model events.
 */
final class ProductCacheService
{
    public const string TAG = 'products';

    public function invalidate(): void
    {
        if (! $this->supportsTags()) {
            return;
        }

        try {
            Cache::tags([self::TAG])->flush();
        } catch (Throwable $e) {
            Log::warning('ProductCacheService invalidate failed', ['exception' => $e->getMessage()]);
        }
    }

    public function supportsTags(): bool
    {
        try {
            return Cache::getStore() instanceof TaggableStore;
        } catch (Throwable) {
            return false;
        }
    }

    public function listKey(int $page, int $perPage): string
    {
        return "products:list:p{$page}:pp{$perPage}";
    }

    public function productKey(int $id): string
    {
        return "products:item:{$id}";
    }
}
