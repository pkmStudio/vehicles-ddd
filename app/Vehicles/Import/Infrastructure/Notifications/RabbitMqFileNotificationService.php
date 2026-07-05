<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Notifications;

use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;

/**
 * Уведомление о готовом файле через RabbitMQ.
 *
 * Файл уже сформирован и лежит в общем хранилище (S3). Здесь только публикуем
 * сообщение в RabbitMQ — сервис с Filament примет его и уведомит пользователя.
 * Publisher — конкретный класс вендора (pkmstudio/rabbit-transport), а не порт:
 * это Infrastructure→Infrastructure, свой RabbitMQPublisherInterface не нужен
 * (см. plan.md §1).
 */
final readonly class RabbitMqFileNotificationService implements FileNotificationServiceInterface
{
    public function __construct(
        private RabbitMQPublisher $publisher,
    ) {}

    public function send(int $userId, string $csvPath, int $filesCount = 1): void
    {
        // TODO: согласовать payload с сервисом-получателем (Filament): какие поля он ждёт.
        $this->publisher->publish(
            new RabbitMessageDTO(
                name: 'FILE_EXPORTED',
                data: [
                    'user_id' => $userId,
                    'path' => $csvPath,
                    'files_count' => $filesCount,
                ],
            ),
        );
    }
}
