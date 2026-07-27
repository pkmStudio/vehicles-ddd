<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\Factories;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\PackDimensionExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum;
use InvalidArgumentException;

/**
 * Выбирает Excel-адаптер Warehouse-экспорта по типу внешнего запроса.
 */
final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    /**
     * Возвращает экспортный адаптер для конкретного типа Warehouse-каталога.
     */
    public function make(
        ExportTypeEnum $type,
        ?int $typeId = null,
        ?KitExportFiltersDTO $kitFilters = null,
        ?KitExportSortDTO $kitSort = null,
    ): FileExportInterface {
        return match ($type) {
            ExportTypeEnum::NomenclatureByType => app()->makeWith(
                abstract: NomenclatureByTypeExportInterface::class,
                parameters: [
                    'typeId' => $typeId ?? throw new InvalidArgumentException('type_id обязателен для экспорта номенклатуры'),
                ],
            ),
            ExportTypeEnum::PackDimension => app(
                abstract: PackDimensionExportInterface::class,
            ),
            ExportTypeEnum::Kit => $this->kitExport(
                filters: $kitFilters,
                sort: $kitSort,
            ),
            ExportTypeEnum::WiperAdapterAudit => app(
                abstract: WiperAdapterAuditExportInterface::class,
            ),
        };
    }

    /**
     * Создаёт адаптер Kit Export с явными фильтрами и сортировкой.
     */
    private function kitExport(?KitExportFiltersDTO $filters, ?KitExportSortDTO $sort): FileExportInterface
    {
        $filters ??= new KitExportFiltersDTO;
        $sort ??= new KitExportSortDTO;

        return app()->makeWith(
            abstract: KitExportInterface::class,
            parameters: [
                'filters' => $filters,
                'sort' => $sort,
            ],
        );
    }
}
