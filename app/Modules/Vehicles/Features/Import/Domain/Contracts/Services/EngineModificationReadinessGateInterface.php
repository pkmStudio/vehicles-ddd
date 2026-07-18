<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services;

interface EngineModificationReadinessGateInterface
{
    public function markEnginesImported(): void;

    public function markModificationsImported(): void;

    public function reset(): void;
}
