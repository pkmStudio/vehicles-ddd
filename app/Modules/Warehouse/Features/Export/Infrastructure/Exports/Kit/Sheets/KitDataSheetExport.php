<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit\Sheets;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\KitExportSortDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class KitDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    public function __construct(
        private KitExportServiceInterface $exportService,
        private ?KitExportFiltersDTO $filters = null,
        private ?KitExportSortDTO $sort = null,
    ) {}

    public function title(): string
    {
        return 'Наборы';
    }

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
