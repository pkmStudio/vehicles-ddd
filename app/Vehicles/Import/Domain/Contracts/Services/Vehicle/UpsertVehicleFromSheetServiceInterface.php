<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Vehicle;

use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;

interface UpsertVehicleFromSheetServiceInterface
{
    public function upsertFromRow(array $row): VehicleData;
}
