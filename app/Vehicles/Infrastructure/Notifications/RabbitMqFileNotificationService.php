<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Notifications;

use App\Vehicles\Domain\Contracts\Infrastructure\Messaging\RabbitMQPublisherInterface;
use App\Vehicles\Infrastructure\Messaging\DTOs\RabbitMessageDTO;
use App\Vehicles\Infrastructure\Messaging\Enums\OutboundEventsEnum;
use App\Vehicles\Domain\Contracts\Infrastructure\Notifications\FileNotificationServiceInterface;

/**
 * Уведомление о готовом файле через RabbitMQ.
 *
 * Файл уже сформирован и лежит в общем хранилище (S3). Здесь только публикуем
 * сообщение в RabbitMQ — сервис с Filament примет его и уведомит пользователя.
 */
final readonly class RabbitMqFileNotificationService implements FileNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisherInterface $publisher,
    ) {}

    public function send(int $userId, string $csvPath, int $filesCount = 1): void
    {
        // TODO: согласовать payload с сервисом-получателем (Filament): какие поля он ждёт.
        $this->publisher->publish(
            new RabbitMessageDTO(
                OutboundEventsEnum::FILE_EXPORTED,
                [
                    'user_id' => $userId,
                    'path' => $csvPath,
                    'files_count' => $filesCount,
                ],
            ),
        );
    }
}
