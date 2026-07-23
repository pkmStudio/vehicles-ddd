<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqImportNotificationService implements ImportNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_IMPORT_COMPLETED',
            data: $payload->toArray(),
        ));
    }
}
