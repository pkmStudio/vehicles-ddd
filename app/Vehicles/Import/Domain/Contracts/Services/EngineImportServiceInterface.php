<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services;

use App\Vehicles\Import\Domain\DTOs\EngineImportPlan;

interface EngineImportServiceInterface
{
    public function buildImportPlan(bool $withSparkPlugs = true): EngineImportPlan;
}
