<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command;

interface VehicleCommandImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     *
     * Шаги:
     * 1) Открыть файл через infrastructure import adapter.
     * 2) Передать строки в command import flow автомобилей.
     */
    public function import(string $path): void;
}
