<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Vehicle;

use App\Vehicles\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class VehicleMultiSheetExport implements WithMultipleSheets
{
    private bool $isAllow;

    public function __construct(bool $isAllow = false)
    {
        $this->isAllow = $isAllow;
    }

    public function sheets(): array
    {
        return [
            new VehicleMainSheetExport($this->isAllow),
            new VehicleWipersSheetExport($this->isAllow),
        ];
    }
}
