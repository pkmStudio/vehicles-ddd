<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Infrastructure\Imports\Modification\ModificationCommandImport;
use Maatwebsite\Excel\Facades\Excel;

class StartModificationCommandImport
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(VehicleCommandImported $event): void
    {
        $path = storage_path('vehicles/modifications.csv');
        Excel::queueImport(app(ModificationCommandImport::class), $path);
    }
}
