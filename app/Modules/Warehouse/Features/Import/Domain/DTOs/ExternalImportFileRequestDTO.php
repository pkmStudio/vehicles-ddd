<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

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
        public bool $cleanupAfterImport = true,
    ) {}
}
