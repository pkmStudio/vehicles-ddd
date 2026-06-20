<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle;

use App\Vehicles\Domain\Contracts\Infrastructure\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Domain\DTOs\VehicleExportPlan;
use App\Vehicles\Domain\Enums\InOut\Sheets\VehicleExportSheet;
use App\Vehicles\Infrastructure\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class VehicleMultiSheetExport implements VehicleMultiSheetExportInterface, WithMultipleSheets
{
    private VehicleExportPlan $plan;

    public function __construct(
        ?VehicleExportPlan $plan = null,
    ) {
        $this->plan = $plan ?? VehicleExportPlan::all();
    }

    public function download(string $fileName): BinaryFileResponse
    {
        return Excel::download($this, $fileName);
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(VehicleExportSheet::Main)) {
            $sheets[] = app()->makeWith(
                VehicleMainSheetExport::class,
                ['isAllow' => $this->plan->isAllow],
            );
        }

        if ($this->plan->hasSheet(VehicleExportSheet::Wipers)) {
            $sheets[] = app()->makeWith(
                VehicleWipersSheetExport::class,
                ['isAllow' => $this->plan->isAllow],
            );
        }

        return $sheets;
    }
}
