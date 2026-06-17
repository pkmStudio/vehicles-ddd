<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Commands\Contracts\VehicleCommandInterface;
use App\Vehicles\Models\Vehicle;

final class VehicleCommand implements VehicleCommandInterface
{
    public function create(array $attributes): Vehicle
    {
        return Vehicle::query()->create($attributes);
    }

    public function update(Vehicle $vehicle, array $attributes): Vehicle
    {
        $vehicle->update($attributes);

        return $vehicle;
    }

    public function updateOrCreate(array $attributes, array $values = []): Vehicle
    {
        return Vehicle::query()->updateOrCreate($attributes, $values);
    }

    public function delete(Vehicle $vehicle): bool
    {
        return (bool) $vehicle->delete();
    }
}
