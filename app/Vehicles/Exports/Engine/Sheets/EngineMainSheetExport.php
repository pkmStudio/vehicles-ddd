<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Engine\Sheets;

use App\Vehicles\Models\Engine;
use App\Vehicles\Traits\HasEngineBaseData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class EngineMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    use HasEngineBaseData;

    public function title(): string
    {
        return 'Двигатели';
    }

    public function collection(): Collection
    {
        return Engine::query()->get();
    }

    public function map($row): array
    {
        return $this->getBaseData($row);
    }

    public function headings(): array
    {
        return $this->getBaseHeadings();
    }
}
