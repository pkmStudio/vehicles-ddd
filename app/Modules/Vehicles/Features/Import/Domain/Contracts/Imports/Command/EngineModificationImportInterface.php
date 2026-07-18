<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command;

interface EngineModificationImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     */
    public function import(string $path): void;
}
