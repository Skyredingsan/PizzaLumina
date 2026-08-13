<?php

declare(strict_types=1);

return [
    'cache' => [
        'list_ttl' => (int) env('PRODUCT_CACHE_LIST_TTL', 3600),
        'item_ttl' => (int) env('PRODUCT_CACHE_ITEM_TTL', 3600),
        'max_per_page' => (int) env('PRODUCT_MAX_PER_PAGE', 100),
    ],
];
