<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Excel-адаптер, который сохраняет лист Warehouse-наборов в xlsx-файл.
 */
final readonly class KitExport implements FromCollection, KitExportInterface, WithHeadings, WithMapping, WithTitle
{
    /**
     * Получает сервис подготовки строк и заголовков набора.
     */
    public function __construct(
        private KitExportServiceInterface $exportService,
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
     * Возвращает название листа наборов.
     */
    public function title(): string
    {
        return 'Наборы';
    }

    /**
     * Возвращает коллекцию наборов для maatwebsite/excel.
     */
    public function collection(): Collection
    {
        $filters = $this->filters ?? new KitExportFiltersDTO;
        $sort = $this->sort ?? new KitExportSortDTO;

        return $this->exportService->getRows(
            filters: $filters,
            sort: $sort,
        );
    }

    /**
     * Мапит одну строку набора в плоский массив значений Excel.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapRow($row);
    }

    /**
     * Возвращает заголовки листа наборов.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings();
    }
}
