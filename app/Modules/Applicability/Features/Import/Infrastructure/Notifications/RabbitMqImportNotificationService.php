<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqImportNotificationService implements ImportNotificationServiceInterface
{
    /**
     * Получает RabbitMQ publisher для исходящих import notifications.
     *
     * Шаги:
     * 1. Сохраняет publisher transport package.
     * 2. Оставляет сбор message name и payload в методе отправки.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат import workflow в RabbitMQ.
     *
     * Шаги:
     * 1. Преобразует локальный notification DTO в wire payload.
     * 2. Оборачивает payload в Rabbit message `APPLICABILITY_IMPORT_COMPLETED`.
     * 3. Передает сообщение в RabbitMQ publisher.
     */
    public function notifyImportCompleted(ImportCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_IMPORT_COMPLETED',
            data: $payload->toArray(),
        ));
    }
}
