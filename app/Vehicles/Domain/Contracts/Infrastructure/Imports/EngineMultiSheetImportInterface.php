<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Imports;

use App\Vehicles\Domain\DTOs\EngineImportPlan;

interface EngineMultiSheetImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     */
    public function import(string $path, ?EngineImportPlan $plan = null): void;
}
