<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Domain\Models\Vehicle;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    public function create(VehicleData $data): Vehicle
    {
        return Vehicle::query()->create($data->toArray());
    }

    public function update(Vehicle $vehicle, VehicleData $data): Vehicle
    {
        $vehicle->update($data->toArray());

        return $vehicle;
    }

    public function upsertByMsId(VehicleData $data): Vehicle
    {
        return Vehicle::query()->updateOrCreate(
            ['ms_id' => $data->msId],
            $data->toArray(),
        );
    }

    public function delete(Vehicle $vehicle): bool
    {
        return (bool) $vehicle->delete();
    }
}
