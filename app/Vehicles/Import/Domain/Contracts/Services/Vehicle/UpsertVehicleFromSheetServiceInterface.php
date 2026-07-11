<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Vehicle;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromSheetServiceInterface
{
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData;
}
