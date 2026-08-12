<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;

interface CalculationNotificationServiceInterface
{
    /**
     * Отправляет уведомление о завершении расчета применяемости.
     *
     * Шаги:
     * 1. Принимает итоговый notification DTO с operation id и счетчиками результата.
     * 2. Преобразует DTO в транспортный payload.
     * 3. Публикует сообщение во внешний notification transport.
     */
    public function notifyCalculationCompleted(CalculationCompletionNotificationDTO $payload): void;
}
