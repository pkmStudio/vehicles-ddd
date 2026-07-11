<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | External Import Cache
    |--------------------------------------------------------------------------
    |
    | Cache-ключи внешнего запуска импорта. Шаблоны принимают runId через sprintf().
    |
    */

    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'vehicles_external_import_accepted_%s',
                'cleanup' => 'vehicles_external_import_cleanup_%s',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Failure Cache
    |--------------------------------------------------------------------------
    |
    | Cache-ключи ошибок импорта. Шаблоны принимают runId через sprintf().
    |
    */

    'failures' => [
        'disk' => env('IMPORT_FAILURES_REPORT_DISK', 'local'),

        'cache' => [
            'keys' => [
                'vehicle_import_failures' => 'vehicle_import_failures_%s',
                'vehicle_import_failures_lock' => 'vehicle_import_failures_lock_%s',
                'engine_import_failures' => 'engine_import_failures_%s',
                'engine_import_failures_lock' => 'engine_import_failures_lock_%s',
            ],
        ],
    ],
];
