<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | External Export Cache
    |--------------------------------------------------------------------------
    |
    | Cache-ключ идемпотентности внешнего запроса на экспорт. Шаблон принимает
    | runId через sprintf(). Симметрично vehicles/import.php:external.cache.
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
    | используется общий S3-compatible disk `exports`, чтобы `dan-center` мог
    | получить готовый файл по пути из RabbitMQ-события.
    |
    */

    'output' => [
        'disk' => env('VEHICLES_EXPORT_OUTPUT_DISK', 'exports'),
        'directory' => 'exports',

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
