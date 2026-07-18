<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class EngineSparkPlugsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private EngineExportServiceInterface $exportService,
    ) {
    }

    public function title(): string
    {
        return 'Свечи зажигания';
    }

    public function collection(): Collection
    {
        return $this->exportService->getSparkPlugRows();
    }

    public function map($row): array
    {
        return $this->exportService->mapSparkPlugRow($row);
    }

    public function headings(): array
    {
        return $this->exportService->getSparkPlugHeadings();
    }
}
