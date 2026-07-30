<?php

declare(strict_types=1);

return [
    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'warehouse_external_export_accepted_%s',
            ],
        ],
    ],

    'output' => [
        'disk' => env('WAREHOUSE_EXPORT_OUTPUT_DISK', 'exports'),
        'directory' => 'exports',
        'retention_hours' => (int) env('WAREHOUSE_EXPORT_RETENTION_HOURS', 24),
    ],
];
