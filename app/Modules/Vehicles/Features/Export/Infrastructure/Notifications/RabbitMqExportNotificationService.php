<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Уведомление о завершении экспорта через RabbitMQ — публикует зарезервированное,
 * но ранее не использовавшееся исходящее событие VEHICLES_FILE_EXPORTED
 * (config/rabbit-transport.php:outbound). Publisher — конкретный класс вендора
 * (pkmstudio/rabbit-transport), а не порт: Infrastructure→Infrastructure, свой
 * RabbitMQPublisherInterface не нужен (см. Import\Infrastructure\Notifications\
 * RabbitMqFileNotificationService, тот же принцип).
 */
final readonly class RabbitMqExportNotificationService implements ExportNotificationServiceInterface
{
    /**
     * Получить publisher RabbitMQ transport.
     *
     * Шаги:
     * - Принять concrete publisher инфраструктурного transport-пакета.
     * - Сохранить его для публикации исходящих export-событий.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Опубликовать событие успешного завершения экспорта.
     *
     * Шаги:
     * - Сформировать RabbitMQ message с именем VEHICLES_FILE_EXPORTED.
     * - Преобразовать notification DTO в payload сообщения.
     * - Отправить сообщение через RabbitMQ publisher.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'VEHICLES_FILE_EXPORTED',
            data: $payload->toArray(),
        );
        $this->publisher->publish($message);
    }
}
