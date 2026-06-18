<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Infrastructure\Imports\Vehicle\VehicleCommandImport;
use Maatwebsite\Excel\Facades\Excel;

final readonly class StartVehicleImportListener
{
    public function __construct(
        private VehicleCommandImport $import,
    ) {}

    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/vehicles.csv');
        Excel::queueImport($this->import, $path);
    }
}
