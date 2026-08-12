<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;

/**
 * Порт публикации результата Vehicles export во внешний транспорт.
 */
interface ExportNotificationServiceInterface
{
    /**
     * Отправляет notification о завершении export request.
     *
     * Шаги:
     * 1) Преобразовать DTO результата экспорта в outbound payload.
     * 2) Опубликовать payload во внешний транспорт.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
