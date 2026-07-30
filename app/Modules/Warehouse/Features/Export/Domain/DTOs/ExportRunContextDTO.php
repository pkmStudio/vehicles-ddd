<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\DTOs;

/**
 * Контекст запуска экспорта по внешнему RabbitMQ-запросу.
 */
final readonly class ExportRunContextDTO
{
    /**
     * Хранит контекст инициатора и уникального прогона экспорта.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
    ) {}
}
