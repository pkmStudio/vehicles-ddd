<?php

declare(strict_types=1);

namespace App\Vehicles\Listeners;

use App\Vehicles\Events\EnginesAndModificationsReady;
use App\Vehicles\Imports\EngineModificationImport;
use Maatwebsite\Excel\Facades\Excel;

class StartEngineModificationImport
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(EnginesAndModificationsReady $event): void
    {
        $path = storage_path('vehicles/engine_modification.csv');
        Excel::queueImport(app(EngineModificationImport::class), $path);
    }
}
