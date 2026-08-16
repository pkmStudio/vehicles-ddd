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
    | Cache-ключи внешнего запуска импорта. Шаблоны принимают operationId через sprintf().
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
    | Cache-ключи ошибок импорта. Шаблоны принимают operationId через sprintf().
    |
    */

    'failures' => [
        'disk' => env('VEHICLES_IMPORT_FAILURES_REPORT_DISK', env('IMPORT_FAILURES_REPORT_DISK', env('FILES_DISK', 's3'))),
        'directory' => env('VEHICLES_IMPORT_FAILURES_REPORT_DIRECTORY', 'dan-vehicles/import'),

        'cache' => [
            'keys' => [
                'vehicle_import_failures' => 'vehicle_import_failures_%s',
                'vehicle_import_failures_lock' => 'vehicle_import_failures_lock_%s',
                'engine_import_failures' => 'engine_import_failures_%s',
                'engine_import_failures_lock' => 'engine_import_failures_lock_%s',
                'manufacturer_import_failures' => 'manufacturer_import_failures_%s',
                'manufacturer_import_failures_lock' => 'manufacturer_import_failures_lock_%s',
                'modification_import_failures' => 'modification_import_failures_%s',
                'modification_import_failures_lock' => 'modification_import_failures_lock_%s',
                'engine_modification_import_failures' => 'engine_modification_import_failures_%s',
                'engine_modification_import_failures_lock' => 'engine_modification_import_failures_lock_%s',
            ],
        ],
    ],
];
