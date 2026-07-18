<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;

/**
 * Уведомление о завершении экспорта через RabbitMQ — публикует зарезервированное,
 * но ранее не использовавшееся исходящее событие FILE_EXPORTED
 * (config/rabbit-transport.php:outbound). Publisher — конкретный класс вендора
 * (pkmstudio/rabbit-transport), а не порт: Infrastructure→Infrastructure, свой
 * RabbitMQPublisherInterface не нужен (см. Import\Infrastructure\Notifications\
 * RabbitMqFileNotificationService, тот же принцип).
 */
final readonly class RabbitMqExportNotificationService implements ExportNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'FILE_EXPORTED',
            data: $payload->toArray(),
        );
        $this->publisher->publish($message);
    }
}
