<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit;

use App\Modules\Shared\Infrastructure\Exports\ReferenceSheetExport;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit\Sheets\KitDataSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Excel-адаптер, который сохраняет лист Warehouse-наборов в xlsx-файл.
 */
final readonly class KitExport implements KitExportInterface, WithMultipleSheets
{
    /**
     * Получает сервис подготовки строк и заголовков набора.
     */
    public function __construct(
        private ?KitExportFiltersDTO $filters = null,
        private ?KitExportSortDTO $sort = null,
    ) {}

    /**
     * Сохраняет xlsx-файл наборов на Storage disk и возвращает путь.
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
        $path = sprintf('%s/warehouse-kits-%s.xlsx', $directory, $context->runId);

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
            app()->makeWith(
                abstract: KitDataSheetExport::class,
                parameters: [
                    'filters' => $this->filters,
                    'sort' => $this->sort,
                ],
            ),
            new ReferenceSheetExport(
                headings: ['Может продаваться отдельно', 'Активен'],
                rows: [
                    ['Да', 'Да'],
                    ['Нет', 'Нет'],
                ],
            ),
        ];
    }
}
