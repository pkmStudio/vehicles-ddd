<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Factories;

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
 * Выбирает Excel-адаптер Warehouse-экспорта на Infrastructure boundary.
 */
final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    public function make(
        ExportTypeEnum $type,
        ?int $typeId = null,
        ?KitExportFiltersDTO $kitFilters = null,
        ?KitExportSortDTO $kitSort = null,
    ): FileExportInterface {
        return match ($type) {
            ExportTypeEnum::NomenclatureByType => app()->makeWith(
                NomenclatureByTypeExportInterface::class,
                ['typeId' => $typeId ?? throw new InvalidArgumentException('type_id обязателен для экспорта номенклатуры')],
            ),
            ExportTypeEnum::PackDimension => app(PackDimensionExportInterface::class),
            ExportTypeEnum::Kit => $this->kitExport($kitFilters, $kitSort),
            ExportTypeEnum::WiperAdapterAudit => app(WiperAdapterAuditExportInterface::class),
        };
    }

    private function kitExport(?KitExportFiltersDTO $filters, ?KitExportSortDTO $sort): FileExportInterface
    {
        return app()->makeWith(
            KitExportInterface::class,
            [
                'filters' => $filters ?? new KitExportFiltersDTO,
                'sort' => $sort ?? new KitExportSortDTO,
            ],
        );
    }
}
