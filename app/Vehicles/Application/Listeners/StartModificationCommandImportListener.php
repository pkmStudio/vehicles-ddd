<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Infrastructure\Imports\Modification\ModificationCommandImport;
use Maatwebsite\Excel\Facades\Excel;

final readonly class StartModificationCommandImportListener
{
    public function __construct(
        private ModificationCommandImport $import,
    ) {}

    public function handle(VehicleCommandImported $event): void
    {
        $path = storage_path('vehicles/modifications.csv');
        Excel::queueImport($this->import, $path);
    }
}
