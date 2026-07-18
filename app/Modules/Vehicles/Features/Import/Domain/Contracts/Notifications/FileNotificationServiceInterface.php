<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;

interface FileNotificationServiceInterface
{
    /**
     * Отправляет статус завершения импорта внешним сервисам.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
