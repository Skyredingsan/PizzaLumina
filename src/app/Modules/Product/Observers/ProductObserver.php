<?php

declare(strict_types=1);

namespace App\Modules\Product\Observers;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Services\ProductCacheService;

final class ProductObserver
{
    public function __construct(
        private readonly ProductCacheService $cacheService,
    ) {
    }

    public function saved(Product $product): void
    {
        $this->cacheService->invalidate();
    }

    public function deleted(Product $product): void
    {
        $this->cacheService->invalidate();
    }

    public function restored(Product $product): void
    {
        $this->cacheService->invalidate();
    }
}
