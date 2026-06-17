<?php

declare(strict_types=1);

namespace App\Vehicles\Listeners;

use App\Vehicles\Events\VehicleCommandImported;
use App\Vehicles\Imports\Modification\ModificationCommandImport;
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
        Excel::queueImport(new ModificationCommandImport, $path);
    }
}
