<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Domain\Contracts\Imports\ModificationCommandImportInterface;

final readonly class StartModificationCommandImportListener
{
    public function __construct(
        private ModificationCommandImportInterface $import,
    ) {}

    public function handle(VehicleCommandImported $event): void
    {
        $path = storage_path('vehicles/modifications.csv');
        $this->import->import($path);
    }
}
