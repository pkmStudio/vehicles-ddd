<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;

interface ImportNotificationServiceInterface
{
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void;
}
