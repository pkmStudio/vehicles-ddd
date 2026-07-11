<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Notifications;

use App\Vehicles\Export\Domain\DTOs\ExportCompletionNotificationDTO;

interface ExportNotificationServiceInterface
{
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
