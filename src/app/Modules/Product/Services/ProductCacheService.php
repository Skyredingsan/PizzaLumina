<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ProductCacheService
{
    public const string TAG = 'products';

    private int $listTtl;

    private int $itemTtl;

    public function __construct()
    {
        $this->listTtl = (int) config(key: 'product.cache.list_ttl', default: 3600);
        $this->itemTtl = (int) config(key: 'product.cache.item_ttl', default: 3600);
    }

    /**
     * @param  callable(): array<string, mixed>  $loader
     * @return array<string, mixed>
     */
    public function rememberList(int $page, int $perPage, callable $loader): array
    {
        if (! $this->supportsTags() || $this->listTtl <= 0) {
            return $loader();
        }

        $key = $this->listKey(page: $page, perPage: $perPage);

        try {
            $cached = Cache::tags([self::TAG])->get(key: $key);
            if (is_array(value: $cached)) {
                return $cached;
            }

            $value = $loader();
            Cache::tags([self::TAG])->put(key: $key, value: $value, ttl: $this->listTtl);

            return $value;
        } catch (Throwable $e) {
            Log::warning('ProductCacheService list cache failed', ['exception' => $e->getMessage()]);

            return $loader();
        }
    }

    /**
     * @param  callable(): array<string, mixed>  $loader
     * @return array<string, mixed>
     */
    public function rememberProduct(int $id, callable $loader): array
    {
        if (! $this->supportsTags() || $this->itemTtl <= 0) {
            return $loader();
        }

        $key = $this->productKey(id: $id);

        try {
            $cached = Cache::tags([self::TAG])->get(key: $key);
            if (is_array(value: $cached)) {
                return $cached;
            }

            $value = $loader();
            Cache::tags([self::TAG])->put(key: $key, value: $value, ttl: $this->itemTtl);

            return $value;
        } catch (Throwable $e) {
            Log::warning('ProductCacheService item cache failed', ['id' => $id, 'exception' => $e->getMessage()]);

            return $loader();
        }
    }

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
