<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command;

use Illuminate\Support\Collection;

interface EnginesCodeImportInterface
{
    /**
     * Прочитать файл и вернуть строки первого листа.
     *
     * Шаги:
     * 1) Открыть файл engine codes import.
     * 2) Вернуть collection строк первого листа.
     */
    public function parse(string $path): Collection;
}
