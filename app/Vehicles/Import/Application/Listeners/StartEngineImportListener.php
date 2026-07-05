<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Listeners;

use App\Vehicles\Import\Domain\Contracts\Imports\EngineCommandImportInterface;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

final readonly class StartEngineImportListener
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
