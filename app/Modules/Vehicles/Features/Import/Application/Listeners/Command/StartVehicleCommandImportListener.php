<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

final readonly class StartVehicleCommandImportListener
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
