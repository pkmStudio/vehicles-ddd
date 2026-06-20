<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\Services;

interface EngineModificationReadinessGateInterface
{
    public function markEnginesImported(): void;

    public function markModificationsImported(): void;

    public function reset(): void;
}
