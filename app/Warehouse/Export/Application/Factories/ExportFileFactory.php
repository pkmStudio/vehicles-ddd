<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Application\Factories;

use App\Warehouse\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Warehouse\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Warehouse\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Warehouse\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Warehouse\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\Enums\ExportTypeEnum;
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
