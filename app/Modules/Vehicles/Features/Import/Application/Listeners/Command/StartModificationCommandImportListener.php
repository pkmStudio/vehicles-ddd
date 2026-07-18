<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleCommandImported;

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
