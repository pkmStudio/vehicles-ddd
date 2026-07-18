<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;

interface VehicleExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(VehicleData $vehicle): array;
}
