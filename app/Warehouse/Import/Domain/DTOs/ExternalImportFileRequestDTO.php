<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\DTOs;

use App\Warehouse\Import\Domain\Enums\ImportTypeEnum;

/**
 * DTO входящей команды на запуск импорта Warehouse-каталога по внешнему RabbitMQ-запросу.
 */
final readonly class ExternalImportFileRequestDTO
{
    /**
     * Хранит валидированный payload внешнего запроса на Warehouse-импорт.
     */
    public function __construct(
        public int $userId,
        public string $runId,
        public ImportTypeEnum $importType,
        public string $disk,
        public string $path,
    ) {}
}
