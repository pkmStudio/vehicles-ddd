<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Notifications;

use App\Vehicles\Infrastructure\Messaging\DTOs\RabbitMessageDTO;
use App\Vehicles\Infrastructure\Messaging\Enums\OutboundEventsEnum;
use App\Vehicles\Infrastructure\Messaging\RabbitMQPublisher;
use App\User\Models\User;
use App\Vehicles\Domain\Contracts\Notifications\FileNotificationServiceInterface;

/**
 * Уведомление о готовом файле через RabbitMQ.
 *
 * Файл уже сформирован и лежит в общем хранилище (S3). Здесь только публикуем
 * сообщение в RabbitMQ — сервис с Filament примет его и уведомит пользователя.
 */
final readonly class RabbitMqFileNotificationService implements FileNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function send(User $user, string $csvPath, int $filesCount = 1): void
    {
        // TODO: согласовать payload с сервисом-получателем (Filament): какие поля он ждёт.
        $this->publisher->publish(
            new RabbitMessageDTO(
                OutboundEventsEnum::FILE_EXPORTED,
                [
                    'user_id' => $user->id,
                    'path' => $csvPath,
                    'files_count' => $filesCount,
                ],
            ),
        );
    }
}
