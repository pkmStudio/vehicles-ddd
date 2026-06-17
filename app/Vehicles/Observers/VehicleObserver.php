<?php

declare(strict_types=1);

namespace App\Vehicles\Observers;

use App\Vehicles\Jobs\InvalidateMpCardsByVehicleJob;
use App\Vehicles\Models\Vehicle;

class VehicleObserver
{
    public function updated(Vehicle $vehicle): void
    {
        InvalidateMpCardsByVehicleJob::dispatch((int) $vehicle->id);
    }
}
