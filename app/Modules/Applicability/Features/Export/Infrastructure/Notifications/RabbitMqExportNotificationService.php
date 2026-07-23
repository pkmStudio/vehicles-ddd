<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqExportNotificationService implements ExportNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_FILE_EXPORTED',
            data: $payload->toArray(),
        ));
    }
}
