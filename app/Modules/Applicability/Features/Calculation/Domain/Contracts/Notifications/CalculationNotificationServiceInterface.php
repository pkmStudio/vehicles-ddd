<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;

interface CalculationNotificationServiceInterface
{
    public function notifyCalculationCompleted(CalculationCompletionNotificationDTO $payload): void;
}
