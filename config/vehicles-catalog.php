<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Vehicle catalog mutation idempotency
    |--------------------------------------------------------------------------
    |
    | External create/update/delete commands are deduplicated by operation_id.
    | The key is kept long enough to cover normal RabbitMQ redeliveries.
    |
    */
    'mutations' => [
        'cache' => [
            'keys' => [
                'accepted' => 'vehicles:catalog:mutation:%s:accepted',
            ],
            'ttl_seconds' => (int) env('VEHICLES_CATALOG_MUTATION_TTL', 86400),
        ],
    ],
];
