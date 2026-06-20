<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Imports;

interface ManufacturerCommandImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     */
    public function import(string $path): void;
}
