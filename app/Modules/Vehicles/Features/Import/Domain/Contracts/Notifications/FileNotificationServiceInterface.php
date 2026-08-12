<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;

interface FileNotificationServiceInterface
{
    /**
     * Отправляет статус завершения импорта внешним сервисам.
     *
     * Шаги:
     * 1) Преобразовать notification DTO в outbound payload.
     * 2) Опубликовать payload во внешний транспорт.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
