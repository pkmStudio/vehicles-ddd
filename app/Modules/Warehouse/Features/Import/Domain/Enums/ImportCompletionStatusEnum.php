<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Enums;

/**
 * Статусы результата Warehouse-импорта, уходящие во внешнее уведомление.
 */
enum ImportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
