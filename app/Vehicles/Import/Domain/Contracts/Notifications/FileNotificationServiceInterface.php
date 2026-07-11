<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Notifications;

use App\Vehicles\Import\Domain\DTOs\ImportCompletionNotificationDTO;

interface FileNotificationServiceInterface
{
    /**
     * Отправляет статус завершения импорта внешним сервисам.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
