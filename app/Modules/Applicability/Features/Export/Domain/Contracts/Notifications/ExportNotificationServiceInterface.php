<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;

interface ExportNotificationServiceInterface
{
    /**
     * Публикует результат завершения export workflow.
     *
     * Шаги:
     * 1. Принимает notification DTO со статусом, operation id, disk и path при успехе.
     * 2. Передает payload во внешний transport реализации.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
