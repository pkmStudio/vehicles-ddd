<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Rows;

use App\Vehicles\Export\Domain\ModelData\VehicleData;

interface VehicleExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(VehicleData $vehicle): array;
}
