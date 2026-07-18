<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Enums;

/**
 * Статус завершения импорта, который уходит во внешние сервисы через RabbitMQ.
 */
enum ImportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';
}
