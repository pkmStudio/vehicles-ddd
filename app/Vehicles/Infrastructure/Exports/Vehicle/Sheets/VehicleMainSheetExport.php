<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle\Sheets;

use App\Vehicles\Domain\Contracts\Application\Export\Services\VehicleExportServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
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
