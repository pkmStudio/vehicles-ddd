<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Factories;

use App\Warehouse\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\Enums\ExportTypeEnum;

/**
 * Порт selector-фабрики, выбирающей адаптер Warehouse-экспорта по enum-типу.
 */
interface ExportFileFactoryInterface
{
    /**
     * Возвращает адаптер экспорта для типа запроса и опционального id Warehouse-типа.
     */
    public function make(
        ExportTypeEnum $type,
        ?int $typeId = null,
        ?KitExportFiltersDTO $kitFilters = null,
        ?KitExportSortDTO $kitSort = null,
    ): FileExportInterface;
}
