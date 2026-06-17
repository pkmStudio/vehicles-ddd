<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Contracts;

use App\Vehicles\Models\Vehicle;

/**
 * Запись Vehicle (write).
 */
interface VehicleCommandInterface
{
    public function create(array $attributes): Vehicle;

    public function update(Vehicle $vehicle, array $attributes): Vehicle;

    public function updateOrCreate(array $attributes, array $values = []): Vehicle;

    public function delete(Vehicle $vehicle): bool;
}
