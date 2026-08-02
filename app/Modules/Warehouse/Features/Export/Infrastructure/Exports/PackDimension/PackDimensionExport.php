<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\PackDimension;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\PackDimensionExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\PackDimensionExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\PackDimension\Sheets\PackDimensionDataSheetExport;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\ReferenceSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Multi-sheet Excel-адаптер для выгрузки упаковочных размеров Warehouse.
 */
final readonly class PackDimensionExport implements PackDimensionExportInterface, WithMultipleSheets
{
    public function __construct(
        private PackDimensionExportServiceInterface $exportService,
    ) {}

    /**
     * Сохраняет xlsx-файл упаковок на Storage disk и возвращает путь.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config(
            key: 'warehouse.export.output.disk',
            default: 'local',
        );
        $directory = (string) config(
            key: 'warehouse.export.output.directory',
            default: 'exports',
        );
        $path = sprintf('%s/warehouse-pack-dimensions-%s.xlsx', $directory, $context->operationId);

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            app(PackDimensionDataSheetExport::class),
            new ReferenceSheetExport(
                headings: ['ID', 'Код типа', 'Тип товара'],
                rows: $this->exportService->getReferenceRows(),
            ),
        ];
    }
}
