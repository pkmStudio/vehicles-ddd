<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Exports\Vehicle;

use App\Vehicles\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Export\Domain\DTOs\ExportRunContextDTO;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class VehicleMultiSheetExport implements VehicleMultiSheetExportInterface, WithMultipleSheets
{
    public function __construct(
        private bool $isAllow = false,
    ) {}

    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('vehicles.export.output.disk', 'local');
        $directory = (string) config('vehicles.export.output.directory', 'exports');
        $path = sprintf('%s/vehicle-catalog-%s.xlsx', $directory, $context->runId);

        ExcelFacade::store($this, $path, $disk, Excel::XLSX);

        return $path;
    }

    public function sheets(): array
    {
        return [
            app()->makeWith(VehicleMainSheetExport::class, ['isAllow' => $this->isAllow]),
            app()->makeWith(VehicleWipersSheetExport::class, ['isAllow' => $this->isAllow]),
        ];
    }
}
