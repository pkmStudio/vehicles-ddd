<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle\Sheets;

use App\Modules\Shared\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    public function __construct(
        private VehicleExportServiceInterface $exportService,
        private bool $isAllow = false,
    ) {}

    public function title(): string
    {
        return 'Основная информация';
    }

    public function collection(): Collection
    {
        return $this->exportService->getMainRows($this->isAllow);
    }

    public function map($row): array
    {
        return $this->exportService->mapMainRow($row);
    }

    public function headings(): array
    {
        return $this->exportService->getMainHeadings();
    }
}
