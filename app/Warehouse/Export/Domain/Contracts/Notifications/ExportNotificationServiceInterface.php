<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Notifications;

use App\Warehouse\Export\Domain\DTOs\ExportCompletionNotificationDTO;

/**
 * Порт исходящего уведомления о завершении Warehouse-экспорта.
 */
interface ExportNotificationServiceInterface
{
    /**
     * Публикует итоговый статус экспорта наружу.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
