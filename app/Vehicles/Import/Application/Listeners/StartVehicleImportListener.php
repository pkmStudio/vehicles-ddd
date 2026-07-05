<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Listeners;

use App\Vehicles\Import\Domain\Contracts\Imports\VehicleCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

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
