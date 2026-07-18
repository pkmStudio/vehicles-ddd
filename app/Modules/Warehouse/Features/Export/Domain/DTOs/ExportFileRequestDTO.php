<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\DTOs;

use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;

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
