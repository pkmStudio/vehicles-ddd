<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Enums;

/**
 * Статусы результата Warehouse-экспорта, уходящие во внешнее уведомление.
 */
enum ExportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
