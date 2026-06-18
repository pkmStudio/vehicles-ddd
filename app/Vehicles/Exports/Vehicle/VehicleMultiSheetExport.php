<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Vehicle;

use App\Vehicles\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class VehicleMultiSheetExport implements WithMultipleSheets
{
    public function __construct(
        private bool $isAllow = false,
    ) {}

    public function sheets(): array
    {
        return [
            app()->makeWith(VehicleMainSheetExport::class, ['isAllow' => $this->isAllow]),
            app()->makeWith(VehicleWipersSheetExport::class, ['isAllow' => $this->isAllow]),
        ];
    }
}
