<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Notifications;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

final readonly class RabbitMqExportNotificationService implements ExportNotificationServiceInterface
{
    /**
     * Получает RabbitMQ publisher для исходящих export notifications.
     *
     * Шаги:
     * 1. Сохраняет publisher transport package.
     * 2. Оставляет сбор конкретного message name в методе отправки.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    /**
     * Публикует результат export workflow в RabbitMQ.
     *
     * Шаги:
     * 1. Преобразует локальный notification DTO в wire payload.
     * 2. Оборачивает payload в Rabbit message `APPLICABILITY_FILE_EXPORTED`.
     * 3. Передает сообщение в RabbitMQ publisher.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void
    {
        $this->publisher->publish(new RabbitMessageDTO(
            name: 'APPLICABILITY_FILE_EXPORTED',
            data: $payload->toArray(),
        ));
    }
}
