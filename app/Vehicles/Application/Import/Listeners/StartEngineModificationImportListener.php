<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineModificationImportInterface;
use App\Vehicles\Domain\Events\EnginesAndModificationsReady;

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
