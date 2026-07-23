<?php

declare(strict_types=1);

return [
    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'applicability_external_export_accepted_%s',
            ],
        ],
    ],

    'output' => [
        'disk' => env('APPLICABILITY_EXPORT_OUTPUT_DISK', 'local'),
        'directory' => 'exports',
        'retention_hours' => (int) env('APPLICABILITY_EXPORT_RETENTION_HOURS', 24),
    ],
];
