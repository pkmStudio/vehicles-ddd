<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Notifications;

use App\Warehouse\Import\Domain\DTOs\ImportCompletionNotificationDTO;

/**
 * Порт исходящего уведомления о завершении Warehouse-импорта.
 */
interface ImportNotificationServiceInterface
{
    /**
     * Публикует итоговый статус импорта наружу.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
