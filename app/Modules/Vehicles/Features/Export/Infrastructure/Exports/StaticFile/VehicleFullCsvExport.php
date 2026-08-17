<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\StaticFile;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleFullCsvExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Adapter экспорта полного CSV-файла Vehicles.
 */
final readonly class VehicleFullCsvExport extends AbstractStaticVehicleFileExport implements VehicleFullCsvExportInterface
{
    /**
     * Возвращает тип export-а для полного CSV.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::VehicleFullCsv;
    }

    /**
     * Возвращает локальный storage path полного CSV.
     */
    protected function sourcePath(): string
    {
        return 'vehicles/another/car_data_full.csv';
    }
}
