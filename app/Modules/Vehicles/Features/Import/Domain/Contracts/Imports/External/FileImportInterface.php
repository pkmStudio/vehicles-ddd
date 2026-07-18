<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;

/**
 * Общий контракт импорта файла с явным контекстом запуска.
 */
interface FileImportInterface
{
    /**
     * Запустить импорт из файла $path на указанном disk. Транспорт (Excel) — в реализации.
     */
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void;
}
