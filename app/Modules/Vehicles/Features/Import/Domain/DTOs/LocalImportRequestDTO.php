<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs;

/**
 * Описывает локальный запрос импорта Vehicles, который нужно опубликовать во входящий RabbitMQ-flow.
 */
final readonly class LocalImportRequestDTO
{
    /**
     * Получает routing/event параметры и явный контекст запуска импорта.
     */
    public function __construct(
        public string $eventName,
        public string $routingKey,
        public string $importType,
        public string $disk,
        public string $path,
        public int $userId,
        public string $operationId,
        public bool $cleanupAfterImport,
    ) {}
}
