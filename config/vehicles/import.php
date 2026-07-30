<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Command Import
    |--------------------------------------------------------------------------
    |
    | Путь к первому файлу консольного TecDoc-каскада относительно storage_path().
    |
    */

    'command' => [
        'manufacturers_path' => env('VEHICLES_IMPORT_MANUFACTURERS_PATH', 'vehicles/manufacturers.csv'),
    ],

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
        'disk' => env('IMPORT_FAILURES_REPORT_DISK', 'exports'),

        'cache' => [
            'keys' => [
                'vehicle_import_failures' => 'vehicle_import_failures_%s',
                'vehicle_import_failures_lock' => 'vehicle_import_failures_lock_%s',
                'engine_import_failures' => 'engine_import_failures_%s',
                'engine_import_failures_lock' => 'engine_import_failures_lock_%s',
                'manufacturer_import_failures' => 'manufacturer_import_failures_%s',
                'manufacturer_import_failures_lock' => 'manufacturer_import_failures_lock_%s',
            ],
        ],
    ],
];
