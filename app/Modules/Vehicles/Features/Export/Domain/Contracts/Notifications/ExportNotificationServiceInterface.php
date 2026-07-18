<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;

interface ExportNotificationServiceInterface
{
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
