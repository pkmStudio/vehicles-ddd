<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services;

use App\Vehicles\Import\Domain\Contracts\Services\EngineImportServiceInterface;
use App\Vehicles\Import\Domain\DTOs\EngineImportPlan;

final readonly class EngineImportService implements EngineImportServiceInterface
{
    public function buildImportPlan(bool $withSparkPlugs = true): EngineImportPlan
    {
        return $withSparkPlugs ? EngineImportPlan::all() : EngineImportPlan::mainOnly();
    }
}
