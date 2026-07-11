<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Vehicle;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;

interface UpsertVehicleFromTdRowServiceInterface
{
    public function upsertFromRow(VehicleTdRowDTO $row): ?VehicleData;
}
