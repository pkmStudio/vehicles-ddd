<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Vehicle;

use App\Vehicles\DTOs\Vehicle\VehicleData;
use App\Vehicles\Models\Vehicle;

interface VehicleCommandInterface
{
    public function create(VehicleData $data): Vehicle;

    public function update(Vehicle $vehicle, VehicleData $data): Vehicle;

    /** Upsert по натуральному ключу ms_id. */
    public function upsertByMsId(VehicleData $data): Vehicle;

    public function delete(Vehicle $vehicle): bool;
}
