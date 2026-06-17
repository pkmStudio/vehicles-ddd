<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Vehicle\Sheets;

use App\Vehicles\Models\Vehicle;
use App\Vehicles\Traits\BuildExportDetails;
use App\Vehicles\Traits\HasVehicleBaseData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final readonly class VehicleMainSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    use BuildExportDetails;
    use HasVehicleBaseData;

    private bool $isAllow;

    public function __construct(bool $isAllow = false)
    {
        $this->isAllow = $isAllow;
    }

    public function title(): string
    {
        return 'Основная информация';
    }

    public function collection(): Collection
    {
        $query = Vehicle::query()
            ->with(['manufacturer', 'parent']);

        if ($this->isAllow) {
            $query->where('is_allow', true);
        }

        return $query->get();
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        return $this->getBaseData($row);
    }

    public function headings(): array
    {
        return $this->getBaseHeadings();
    }
}
