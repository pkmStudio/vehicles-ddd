<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCommandImport;
use Maatwebsite\Excel\Facades\Excel;

class StartEngineImport
{
    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/engines.csv');
        Excel::queueImport(app(EngineCommandImport::class), $path);
    }
}
