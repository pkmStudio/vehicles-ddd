<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Notifications;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;

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
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void
    {
        $message = new RabbitMessageDTO(
            name: 'IMPORT_COMPLETED',
            data: $payload->toArray(),
        );
        $this->publisher->publish($message);
    }
}
