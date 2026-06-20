<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services;

use App\Vehicles\Domain\DTOs\EngineImportPlan;

interface EngineImportServiceInterface
{
    public function buildImportPlan(bool $withSparkPlugs = true): EngineImportPlan;
}
