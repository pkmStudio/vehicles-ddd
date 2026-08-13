<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Notifications;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Публикует событие завершения Warehouse-импорта в RabbitMQ через rabbit-transport.
 */
final readonly class RabbitMqImportNotificationService implements ImportNotificationServiceInterface
{
    /**
     * Получает publisher пакета rabbit-transport.
     *
     * Шаги:
     * 1) Принять RabbitMQ publisher.
     * 2) Сохранить publisher для отправки completion-событий.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Отправляет wire-payload завершения импорта в настроенный outbound.
     *
     * Шаги:
     * 1) Преобразовать completion DTO в массив wire-payload.
     * 2) Собрать RabbitMessageDTO с именем WAREHOUSE_IMPORT_COMPLETED.
     * 3) Опубликовать сообщение через rabbit-transport.
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
