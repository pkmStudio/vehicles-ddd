<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Export\Services\Rows;

use App\Vehicles\Domain\Models\Vehicle;

interface VehicleExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(Vehicle $vehicle): array;
}
