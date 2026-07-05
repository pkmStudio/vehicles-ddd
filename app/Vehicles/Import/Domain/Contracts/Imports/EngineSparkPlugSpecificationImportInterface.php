<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports;

use App\Vehicles\Import\Domain\DTOs\ImportRunContext;

interface EngineSparkPlugSpecificationImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     * $context — явный инициатор прогона (userId опционален, runId — всегда), вместо
     * неявного Auth::id().
     */
    public function import(string $path, ImportRunContext $context): void;
}
