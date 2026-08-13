<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;

interface ImportNotificationServiceInterface
{
    /**
     * Публикует результат завершения import workflow.
     *
     * Шаги:
     * 1. Принимает notification DTO со статусом, operation id и ссылкой на отчет ошибок.
     * 2. Передает payload во внешний transport реализации.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
