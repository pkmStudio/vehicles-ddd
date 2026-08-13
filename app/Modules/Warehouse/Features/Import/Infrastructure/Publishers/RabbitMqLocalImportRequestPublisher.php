<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Publishers;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * RabbitMQ adapter публикации локального Warehouse import request.
 */
final readonly class RabbitMqLocalImportRequestPublisher implements LocalImportRequestPublisherInterface
{
    /**
     * Получает RabbitMQ publisher и PSR logger.
     *
     * Шаги:
     * 1) Принять RabbitMQ publisher.
     * 2) Принять logger для ошибок публикации.
     * 3) Сохранить зависимости adapter'а.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Публикует request в RabbitMQ и логирует только ошибки публикации.
     *
     * Шаги:
     * 1) Собрать RabbitMessageDTO из LocalImportRequestDTO.
     * 2) Опубликовать сообщение с routing key из запроса.
     * 3) На exception записать error-log и вернуть false.
     * 4) Если publisher вернул false, записать error-log и вернуть false.
     * 5) Вернуть true при успешной публикации.
     */
    public function publish(LocalImportRequestDTO $request): bool
    {
        try {
            $published = $this->publisher->publish(
                message: new RabbitMessageDTO(
                    name: $request->eventName,
                    data: [
                        'user_id' => $request->userId,
                        'operation_id' => $request->operationId,
                        'import_type' => $request->importType,
                        'disk' => $request->disk,
                        'path' => $request->path,
                        'cleanup_after_import' => $request->cleanupAfterImport,
                    ],
                ),
                routingKey: $request->routingKey,
            );
        } catch (Throwable $e) {
            $this->logger->error('Ошибка публикации RabbitMQ-запроса импорта Warehouse.', [
                'event' => $request->eventName,
                'routing_key' => $request->routingKey,
                'operation_id' => $request->operationId,
                'disk' => $request->disk,
                'path' => $request->path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $published) {
            $this->logger->error('RabbitMQ publisher вернул false при публикации Warehouse import request.', [
                'event' => $request->eventName,
                'routing_key' => $request->routingKey,
                'operation_id' => $request->operationId,
                'disk' => $request->disk,
                'path' => $request->path,
            ]);

            return false;
        }

        return true;
    }
}
