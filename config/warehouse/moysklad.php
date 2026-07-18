<?php

declare(strict_types=1);

return [
    'queue' => env('WAREHOUSE_MOYSKLAD_QUEUE', 'moysklad'),

    'nomenclature_sync' => [
        'enabled' => (bool) env('WAREHOUSE_MOYSKLAD_NOMENCLATURE_SYNC_ENABLED', false),

        'product_folders' => [
            'enabled' => (bool) env('WAREHOUSE_MOYSKLAD_PRODUCT_FOLDERS_ENABLED', true),
            'cache_ttl_seconds' => (int) env('WAREHOUSE_MOYSKLAD_PRODUCT_FOLDER_CACHE_TTL', 3600),
        ],
    ],
];
