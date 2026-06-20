<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services;

use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineImportServiceInterface;
use App\Vehicles\Domain\DTOs\EngineImportPlan;

final readonly class EngineImportService implements EngineImportServiceInterface
{
    public function buildImportPlan(bool $withSparkPlugs = true): EngineImportPlan
    {
        return $withSparkPlugs ? EngineImportPlan::all() : EngineImportPlan::mainOnly();
    }
}
