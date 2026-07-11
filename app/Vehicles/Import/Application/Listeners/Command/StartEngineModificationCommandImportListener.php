<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Listeners\Command;

use App\Vehicles\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Vehicles\Import\Domain\Events\EnginesAndModificationsReady;

final readonly class StartEngineModificationCommandImportListener
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
