<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Manufacturer\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\ManufacturerExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet adapter производителей автомобилей.
 */
final readonly class ManufacturerSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Инициализирует service сборки строк производителей.
     */
    public function __construct(
        private ManufacturerExportServiceInterface $exportService,
    ) {}

    /**
     * Возвращает название листа производителей.
     */
    public function title(): string
    {
        return 'Производители';
    }

    /**
     * Возвращает строки листа производителей.
     */
    public function collection(): Collection
    {
        return $this->exportService->getRows();
    }

    /**
     * Возвращает headings файла производителей.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings();
    }

    /**
     * Преобразует typed manufacturer snapshot в Excel row.
     *
     * @return array<int, string|int>
     */
    public function map($row): array
    {
        /** @var ManufacturerData $row */
        return $this->exportService->mapRow($row);
    }
}
