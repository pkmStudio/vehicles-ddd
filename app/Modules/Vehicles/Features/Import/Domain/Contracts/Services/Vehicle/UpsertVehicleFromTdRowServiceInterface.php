<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromTdRowServiceInterface
{
    public function upsertFromRow(VehicleTdRowDTO $row): ?VehicleData;
}
