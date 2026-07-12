<?php

declare(strict_types=1);

return [
    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'warehouse_external_import_accepted_%s',
                'cleanup' => 'warehouse_external_import_cleanup_%s',
            ],
        ],
    ],

    'failures' => [
        'disk' => env('IMPORT_FAILURES_REPORT_DISK', 'local'),

        'cache' => [
            'keys' => [
                'nomenclature_import_failures' => 'nomenclature_import_failures_%s',
                'nomenclature_import_failures_lock' => 'nomenclature_import_failures_lock_%s',
                'pack_dimension_import_failures' => 'pack_dimension_import_failures_%s',
                'pack_dimension_import_failures_lock' => 'pack_dimension_import_failures_lock_%s',
                'kit_import_failures' => 'kit_import_failures_%s',
                'kit_import_failures_lock' => 'kit_import_failures_lock_%s',
            ],
        ],
    ],
];
