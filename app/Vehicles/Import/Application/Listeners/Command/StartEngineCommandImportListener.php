<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Listeners\Command;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

final readonly class StartEngineCommandImportListener
{
    public function __construct(
        private EngineCommandImportInterface $import,
    ) {}

    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/engines.csv');
        $this->import->import($path);
    }
}
