<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\DTOs;

use App\Warehouse\Export\Domain\Enums\ExportTypeEnum;

/**
 * DTO входящей команды на запуск экспорта Warehouse-каталога.
 */
final readonly class ExportFileRequestDTO
{
    /**
     * Хранит валидированный payload внешнего запроса на Warehouse-экспорт.
     */
    public function __construct(
        public int $userId,
        public string $runId,
        public ExportTypeEnum $exportType,
        public string $disk,
        public ?int $typeId = null,
        public ?KitExportFiltersDTO $kitFilters = null,
        public ?KitExportSortDTO $kitSort = null,
    ) {}
}
