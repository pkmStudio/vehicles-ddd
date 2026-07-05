<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Imports;

use App\Vehicles\Domain\DTOs\ImportRunContext;
use App\Vehicles\Domain\DTOs\VehicleImportPlan;

interface VehicleMultiSheetImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     * $context — явный инициатор прогона (userId опционален, runId — всегда), вместо
     * неявного Auth::id().
     */
    public function import(string $path, ImportRunContext $context, ?VehicleImportPlan $plan = null): void;
}
