<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Engine\Sheets;

use App\Vehicles\Application\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Infrastructure\Support\EngineExportRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class EngineMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{

    public function __construct(
        private EngineRepositoryInterface $engines,
        private EngineExportRow $engineRow,
    ) {}

    public function title(): string
    {
        return 'Двигатели';
    }

    public function collection(): Collection
    {
        return $this->engines->all();
    }

    public function map($row): array
    {
        return $this->engineRow->getBaseData($row);
    }

    public function headings(): array
    {
        return $this->engineRow->getBaseHeadings();
    }
}
