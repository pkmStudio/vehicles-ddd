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
     *
     * Шаги:
     * 1) Принять DTO с типом импорта, статусом, пользователем и operationId.
     * 2) Преобразовать DTO в transport payload.
     * 3) Отправить уведомление во внешний канал завершения импорта.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
