<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | External Export Cache
    |--------------------------------------------------------------------------
    |
    | Cache-ключ идемпотентности внешнего запроса на экспорт. Шаблон принимает
    | runId через sprintf(). Симметрично vehicles-import.php:external.cache.
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
    | Диск, на который пишутся сгенерированные файлы каталога. 's3' — тот же
    | общий диск, что Import использует для входных файлов (filesystems.
    | files_disk); в этом окружении у него driver=local (см. config/
    | filesystems.php), реальных креды не нужны. Отдельный диск 'exports'
    | (настоящий S3) тоже объявлен в config/filesystems.php — переключить на
    | него можно через VEHICLES_EXPORT_OUTPUT_DISK, когда в окружении будет
    | установлен league/flysystem-aws-s3-v3 и заданы AWS_* креды.
    |
    */

    'output' => [
        'disk' => env('VEHICLES_EXPORT_OUTPUT_DISK', 's3'),

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
