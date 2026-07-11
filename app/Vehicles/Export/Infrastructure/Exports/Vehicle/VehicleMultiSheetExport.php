<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Exports\Vehicle;

use App\Vehicles\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class VehicleMultiSheetExport implements VehicleMultiSheetExportInterface, WithMultipleSheets
{
    public function __construct(
        private bool $isAllow = false,
    ) {}

    public function download(string $fileName): BinaryFileResponse
    {
        return Excel::download($this, $fileName);
    }

    public function sheets(): array
    {
        return [
            app()->makeWith(VehicleMainSheetExport::class, ['isAllow' => $this->isAllow]),
            app()->makeWith(VehicleWipersSheetExport::class, ['isAllow' => $this->isAllow]),
        ];
    }
}
