<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications\CalculationNotificationServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqCalculationNotificationService implements CalculationNotificationServiceInterface
{
    /**
     * Получает RabbitMQ publisher для calculation notifications.
     *
     * Шаги:
     * 1. Сохраняет transport publisher.
     * 2. Оставляет сборку сообщения методу уведомления.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует сообщение о завершении расчета применяемости.
     *
     * Шаги:
     * 1. Преобразует notification DTO в array payload.
     * 2. Создает Rabbit message `APPLICABILITY_CALCULATION_COMPLETED`.
     * 3. Отправляет сообщение через configured RabbitMQ publisher.
     */
    public function notifyCalculationCompleted(CalculationCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_CALCULATION_COMPLETED',
            data: $payload->toArray(),
        ));
    }
}
