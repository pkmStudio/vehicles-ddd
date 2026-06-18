<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCommandImport;
use Maatwebsite\Excel\Facades\Excel;

final readonly class StartEngineImportListener
{
    public function __construct(
        private EngineCommandImport $import,
    ) {}

    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/engines.csv');
        Excel::queueImport($this->import, $path);
    }
}
