<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports;

use App\Vehicles\Import\Domain\DTOs\EngineImportPlan;
use App\Vehicles\Import\Domain\DTOs\ImportRunContext;

interface EngineMultiSheetImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     * $context — явный инициатор прогона (userId опционален, runId — всегда), вместо
     * неявного Auth::id().
     */
    public function import(string $path, ImportRunContext $context, ?EngineImportPlan $plan = null): void;
}
