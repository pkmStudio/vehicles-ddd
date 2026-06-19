<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Domain\Contracts\Imports\EngineModificationImportInterface;

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
