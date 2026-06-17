<?php

declare(strict_types=1);

namespace App\Vehicles\Messaging;

use App\Vehicles\Messaging\DTOs\RabbitMessageDTO;
use Illuminate\Support\Facades\Log;

/**
 * Публикатор сообщений в RabbitMQ exchange
 */
final readonly class RabbitMQPublisher
{
    /**
     * Отправляет сообщение в RabbitMQ exchange:
     * - Сериализует DTO в JSON
     * - Использует connection vehicles_inbox
     * - Отправляет с routing key из enum или кастомным
     * - При ошибке логирует с полным trace
     *
     * @return void
     */
    public function publish(RabbitMessageDTO $message, string $routingKey = ''): bool
    {
        try {
            $connection = \Queue::connection('vehicles_inbox');
            $payload = json_encode($message, JSON_THROW_ON_ERROR);
            $connection->pushRaw(
                $payload,
                $routingKey ?: $message->name->value,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('RabbitMQ: Failed to publish message', [
                'event' => $message->name->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
