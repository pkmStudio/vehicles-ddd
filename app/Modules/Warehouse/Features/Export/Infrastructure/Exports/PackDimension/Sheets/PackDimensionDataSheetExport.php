<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\PackDimension\Sheets;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\PackDimensionExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Лист данных упаковочных размеров Warehouse.
 */
final readonly class PackDimensionDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    public function __construct(
        private PackDimensionExportServiceInterface $exportService,
    ) {}

    public function title(): string
    {
        return 'Упаковки';
    }

    public function collection(): Collection
    {
        return $this->exportService->getRows();
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->exportService->mapRow($row);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->exportService->getHeadings();
    }
}
