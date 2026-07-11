<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Enums;

/**
 * Статус завершения экспорта, который уходит во внешние сервисы через RabbitMQ.
 * В отличие от ImportCompletionStatusEnum, нет CompletedWithErrors: экспорт не
 * ведёт построчного учёта ошибок (см. Import\Infrastructure\Traits\
 * CachesImportFailures) — либо файл целиком собран, либо нет.
 */
enum ExportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
