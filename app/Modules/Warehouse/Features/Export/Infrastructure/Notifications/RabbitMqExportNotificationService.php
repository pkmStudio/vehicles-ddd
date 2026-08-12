<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Notifications;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует событие завершения Warehouse-экспорта в RabbitMQ через rabbit-transport.
 */
final readonly class RabbitMqExportNotificationService implements ExportNotificationServiceInterface
{
    /**
     * Получает publisher внешнего брокера.
     *
     * Шаги:
     * 1) Принять RabbitMQ publisher из rabbit-transport.
     * 2) Сохранить publisher для отправки export completion events.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Отправляет wire-payload завершения экспорта в настроенный outbound.
     *
     * Шаги:
     * 1) Принять DTO итогового статуса экспорта.
     * 2) Собрать RabbitMessageDTO с именем WAREHOUSE_FILE_EXPORTED.
     * 3) Опубликовать сообщение через RabbitMQ publisher.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'WAREHOUSE_FILE_EXPORTED',
            data: $payload->toArray(),
        );

        $this->publisher->publish($message);
    }
}
