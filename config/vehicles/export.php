<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | External Export Cache
    |--------------------------------------------------------------------------
    |
    | Cache-ключ идемпотентности внешнего запроса на экспорт. Шаблон принимает
    | operationId через sprintf(). Симметрично vehicles/import.php:external.cache.
    |
    */

    'external' => [
        'cache' => [
            'ttl_seconds' => 86400,

            'keys' => [
                'accepted' => 'vehicles_external_export_accepted_%s',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Диск и подпапка, куда пишутся сгенерированные файлы каталога. По умолчанию
    | используется общий S3-compatible disk из FILES_DISK и единая S3-структура
    | dan-vehicles/export.
    |
    */

    'output' => [
        'disk' => env('VEHICLES_EXPORT_OUTPUT_DISK', env('FILES_DISK', 's3')),
        'directory' => env('VEHICLES_EXPORT_OUTPUT_DIRECTORY', 'dan-vehicles/export'),

        /*
        | Safety-net: удаление файлов старше этого возраста запланированной
        | командой vehicles:export-cleanup-stale-files (routes/console.php,
        | раз в день). Основной путь очистки — сам принимающий сервис удаляет
        | файл в момент скачивания; это — подстраховка на случай, если он
        | этого не сделал.
        */
        'retention_hours' => (int) env('VEHICLES_EXPORT_RETENTION_HOURS', 24),
    ],
];
