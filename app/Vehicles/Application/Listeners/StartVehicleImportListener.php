<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Domain\Contracts\Imports\VehicleCommandImportInterface;

final readonly class StartVehicleImportListener
{
    public function __construct(
        private VehicleCommandImportInterface $import,
    ) {}

    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/vehicles.csv');
        $this->import->import($path);
    }
}
