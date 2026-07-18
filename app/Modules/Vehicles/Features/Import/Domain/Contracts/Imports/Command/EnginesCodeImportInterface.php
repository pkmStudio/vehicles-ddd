<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command;

use Illuminate\Support\Collection;

interface EnginesCodeImportInterface
{
    /**
     * Прочитать файл и вернуть строки первого листа.
     */
    public function parse(string $path): Collection;
}
