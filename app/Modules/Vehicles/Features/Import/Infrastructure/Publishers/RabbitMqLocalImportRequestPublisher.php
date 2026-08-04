<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Publishers;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestDTO;
use PkmStudio\RabbitTransport\DTOs\RabbitMessageDTO;
use PkmStudio\RabbitTransport\RabbitMQPublisher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * RabbitMQ adapter публикации локального Vehicles import request.
 */
final readonly class RabbitMqLocalImportRequestPublisher implements LocalImportRequestPublisherInterface
{
    /**
     * Получает RabbitMQ publisher и PSR logger.
     */
    public function __construct(
        private RabbitMQPublisher $publisher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Публикует request в RabbitMQ и логирует только ошибки публикации.
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
            $this->logger->error('Ошибка публикации RabbitMQ-запроса импорта Vehicles.', [
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
            $this->logger->error('RabbitMQ publisher вернул false при публикации Vehicles import request.', [
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
