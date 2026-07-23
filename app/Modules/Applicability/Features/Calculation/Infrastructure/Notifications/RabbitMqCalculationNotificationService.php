<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications\CalculationNotificationServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqCalculationNotificationService implements CalculationNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notifyCalculationCompleted(CalculationCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_CALCULATION_COMPLETED',
            data: $payload->toArray(),
        ));
    }
}
