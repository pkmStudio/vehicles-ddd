<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle\Sheets\VehicleWipersSheetExport;

final readonly class VehicleMultiSheetExport extends AbstractMultiSheetExport implements VehicleMultiSheetExportInterface
{
    public function __construct(
        private bool $isAllow = false,
    ) {}

    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Vehicle;
    }

    public function sheets(): array
    {
        return [
            app()->makeWith(VehicleMainSheetExport::class, ['isAllow' => $this->isAllow]),
            app()->makeWith(VehicleWipersSheetExport::class, ['isAllow' => $this->isAllow]),
        ];
    }
}
