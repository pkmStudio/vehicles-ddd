<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\StaticFile;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleCountryCsvExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Adapter экспорта CSV-файла Vehicles со странами.
 */
final readonly class VehicleCountryCsvExport extends AbstractStaticVehicleFileExport implements VehicleCountryCsvExportInterface
{
    /**
     * Возвращает тип export-а для CSV со странами.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::VehicleCountryCsv;
    }

    /**
     * Возвращает локальный storage path CSV со странами.
     */
    protected function sourcePath(): string
    {
        return 'vehicles/another/FULL CV + PC WITH COUNTRY.csv';
    }
}
