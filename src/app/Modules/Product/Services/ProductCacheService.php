<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProductCacheService
{
    public const string TAG = 'products';

    private const int LIST_TTL = 3600;

    private const int ITEM_TTL = 3600;

    /**
     * @param  callable(): array<string, mixed>  $loader
     * @return array<string, mixed>
     */
    public function rememberList(int $page, int $perPage, callable $loader): array
    {
        if (! $this->supportsTags()) {
            return $loader();
        }

        try {
            /** @var array<string, mixed> $result */
            $result = Cache::tags([self::TAG])->remember(
                $this->listKey($page, $perPage),
                self::LIST_TTL,
                $loader,
            );

            return $result;

        } catch (Throwable $e) {
            Log::warning('ProductCacheService list cache failed', ['exception' => $e->getMessage()]);

            return $loader();
        }
    }

    /**
     * @param  callable(): array<string, mixed>  $loader
     * @return array<string, mixed>|null
     */
    public function rememberProduct(int $id, callable $loader): ?array
    {
        if (! $this->supportsTags()) {
            return $loader();
        }

        try {
            /** @var array<string, mixed>|null $result */
            $result = Cache::tags([self::TAG])->remember(
                $this->productKey($id),
                self::ITEM_TTL,
                $loader,
            );

            return $result;
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
