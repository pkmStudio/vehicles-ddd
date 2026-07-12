<?php

declare(strict_types=1);

return [
    'mutations' => [
        'cache' => [
            'ttl_seconds' => (int) env('WAREHOUSE_CATALOG_MUTATION_TTL', 86400),

            'keys' => [
                'accepted' => 'warehouse_catalog_mutation_accepted_%s',
            ],
        ],
    ],
];
