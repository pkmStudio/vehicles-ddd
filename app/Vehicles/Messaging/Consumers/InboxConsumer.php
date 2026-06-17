<?php

declare(strict_types=1);

namespace App\Vehicles\Messaging\Consumers;

use App\Vehicles\Messaging\Enums\InboundEventsEnum;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

/**
 * Потребитель входящих сообщений из RabbitMQ
 */
final class InboxConsumer extends RabbitMQJob
{
    /**
     * Обрабатывает входящее сообщение из очереди:
     * 1. Извлекает displayName из payload
     * 2. Ищет соответствующий InboundEventsEnum
     * 3. Получает конфиг хендлера [Class, Method]
     * 4. Создает объект класса через контейнер
     * 5. Вызывает метод хендлера с данными
     * 6. При успехе — удаляет из очереди
     * 7. При ошибке — логирует и release (повторная попытка)
     *
     * @return void
     */
    public function fire(): void
    {
        $payload = $this->payload();
        $eventName = $payload['displayName'] ?? null;
        try {
            if (! $eventName) {
                \Log::error('RabbitMQ: Missing displayName in payload', $payload);
                $this->delete();
            }

            $event = InboundEventsEnum::tryFrom($eventName);
            if (! $event) {
                \Log::error("No handler defined for event: {$eventName}", $payload);
                $this->delete();
            }

            [$class, $method] = $event->getHandler();
            app($class)->$method($payload['data']);

            $this->delete();
        } catch (\Throwable $e) {
            \Log::error("RabbitMQ Consumer Error [{$eventName}]: ".$e->getMessage(), [
                'payload' => $payload,
                'trace' => $e->getTrace(),
            ]);

            try {
                $this->release(20);
            } catch (AMQPProtocolChannelException $e) {
                \Log::error("RabbitMQ Release Error [{$eventName}]: ".$e->getMessage(), [
                    'payload' => $payload,
                    'trace' => $e->getTrace(),
                ]);
            }
        }
    }

    /**
     * Переопределяем метод, чтобы воркер не искал ключ "job" в JSON.
     * Возвращает displayName из payload или имя класса
     */
    public function getName(): string
    {
        return $this->payload()['displayName'] ?? self::class;
    }

    /**
     * Преобразует raw body в стандартизированный payload:
     * - id: UUID сообщения или генерирует новый
     * - displayName: Имя события из поля name или displayName
     * - data: Данные из поля body или data
     */
    public function payload(): array
    {
        $rawData = json_decode($this->getRawBody(), true);
        return [
            'id' => $rawData['id'] ?? \Str::uuid()->toString(),
            'displayName' => $rawData['name'] ?? $rawData['displayName'] ?? '',
            'data' => $rawData['body'] ?? $rawData['data'] ?? [],
        ];
    }
}