<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;

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
