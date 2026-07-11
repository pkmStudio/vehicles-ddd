<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports\Command;

interface VehicleCommandImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     */
    public function import(string $path): void;
}
