<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromSheetServiceInterface
{
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData;
}
