<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command;

interface EngineModificationImportInterface
{
    /**
     * Запустить импорт из файла $path. Транспорт (Excel) — в реализации.
     *
     * Шаги:
     * 1) Открыть файл через infrastructure import adapter.
     * 2) Передать строки в command import flow связей engine/modification.
     */
    public function import(string $path): void;
}
