<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Exports\Vehicle;

use App\Vehicles\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Vehicles\Application\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Application\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class VehicleMultiSheetExport implements WithMultipleSheets, VehicleMultiSheetExportInterface
{
    public function download(string $fileName): BinaryFileResponse
    {
        return Excel::download($this, $fileName);
    }

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
