<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Notifications;

use App\Warehouse\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Warehouse\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует событие завершения Warehouse-импорта в RabbitMQ через rabbit-transport.
 */
final readonly class RabbitMqImportNotificationService implements ImportNotificationServiceInterface
{
    /**
     * Получает publisher пакета rabbit-transport.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Отправляет wire-payload завершения импорта в настроенный outbound.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'WAREHOUSE_IMPORT_COMPLETED',
            data: $payload->toArray(),
        );

        $this->publisher->publish($message);
    }
}
