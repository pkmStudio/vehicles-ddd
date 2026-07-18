<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;

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
