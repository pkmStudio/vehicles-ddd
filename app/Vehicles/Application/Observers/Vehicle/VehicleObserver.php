<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Observers\Vehicle;

use App\Vehicles\Application\Jobs\Vehicle\InvalidateMpCardsByVehicleJob;
use App\Vehicles\Domain\Models\Vehicle;

class VehicleObserver
{
    public function updated(Vehicle $vehicle): void
    {
        InvalidateMpCardsByVehicleJob::dispatch((int) $vehicle->id);
    }
}
