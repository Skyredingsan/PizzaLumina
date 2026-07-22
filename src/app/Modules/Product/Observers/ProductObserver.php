<?php

declare(strict_types=1);

namespace App\Modules\Product\Observers;

use App\Modules\Product\Services\ProductCacheService;

final readonly class ProductObserver
{
    public function __construct(
        private ProductCacheService $cacheService,
    ) {
    }

    public function saved(): void
    {
        $this->cacheService->invalidate();
    }

    public function deleted(): void
    {
        $this->cacheService->invalidate();
    }

    public function restored(): void
    {
        $this->cacheService->invalidate();
    }
}
