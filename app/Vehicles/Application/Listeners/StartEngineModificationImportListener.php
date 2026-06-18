<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Infrastructure\Imports\EngineModificationImport;
use Maatwebsite\Excel\Facades\Excel;

final readonly class StartEngineModificationImportListener
{
    public function __construct(
        private EngineModificationImport $import,
    ) {}

    public function handle(EnginesAndModificationsReady $event): void
    {
        $path = storage_path('vehicles/engine_modification.csv');
        Excel::queueImport($this->import, $path);
    }
}
