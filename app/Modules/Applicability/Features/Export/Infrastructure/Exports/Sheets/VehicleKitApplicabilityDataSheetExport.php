<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleKitApplicabilityDataSheetExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    public function __construct(
        private VehicleKitApplicabilityExportServiceInterface $service,
    ) {}

    public function title(): string
    {
        return 'Применяемость';
    }

    public function collection(): Collection
    {
        return $this->service->getRows();
    }

    public function map($row): array
    {
        return $this->service->mapRow($row);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->service->getHeadings();
    }
}
