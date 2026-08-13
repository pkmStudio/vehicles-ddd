<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Уведомление о завершении импорта через RabbitMQ.
 *
 * Сообщение включает статус и, при наличии, путь к сформированному файлу отчёта.
 * Publisher — конкретный класс вендора (pkmstudio/rabbit-transport), а не порт:
 * это Infrastructure→Infrastructure, свой RabbitMQPublisherInterface не нужен
 * (см. plan.md §1).
 */
final readonly class RabbitMqFileNotificationService implements FileNotificationServiceInterface
{
    /**
     * Получить RabbitMQ publisher.
     *
     * Шаги:
     * 1) Принять concrete publisher инфраструктурного transport-пакета.
     * 2) Сохранить его для публикации import completion events.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Опубликовать событие завершения import flow.
     *
     * Шаги:
     * 1) Сформировать RabbitMQ message VEHICLES_IMPORT_COMPLETED.
     * 2) Преобразовать notification DTO в payload сообщения.
     * 3) Отправить сообщение через RabbitMQ publisher.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'VEHICLES_IMPORT_COMPLETED',
            data: $payload->toArray(),
        );
        $this->publisher->publish($message);
    }
}
