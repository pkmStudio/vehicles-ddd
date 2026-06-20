<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Domain\Contracts\Imports\EngineCommandImportInterface;
use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;

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
