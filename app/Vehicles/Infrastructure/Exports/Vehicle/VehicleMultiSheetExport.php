<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Vehicle;

use App\Vehicles\Domain\Contracts\Infrastructure\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\VehicleMainSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\VehicleWipersSheetExportInterface;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class VehicleMultiSheetExport implements VehicleMultiSheetExportInterface, WithMultipleSheets
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
            app()->makeWith(
                VehicleMainSheetExportInterface::class,
                ['isAllow' => $this->isAllow],
            ),
            app()->makeWith(
                VehicleWipersSheetExportInterface::class,
                ['isAllow' => $this->isAllow],
            ),
        ];
    }
}
