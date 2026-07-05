<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Listeners;

use App\Vehicles\Import\Domain\Contracts\Imports\EngineModificationImportInterface;
use App\Vehicles\Import\Domain\Events\EnginesAndModificationsReady;

final readonly class StartEngineModificationImportListener
{
    public function __construct(
        private EngineModificationImportInterface $import,
    ) {}

    public function handle(EnginesAndModificationsReady $event): void
    {
        $path = storage_path('vehicles/engine_modification.csv');
        $this->import->import($path);
    }
}
