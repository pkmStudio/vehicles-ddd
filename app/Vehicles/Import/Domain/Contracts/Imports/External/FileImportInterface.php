<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Imports\External;

use App\Vehicles\Import\Domain\DTOs\ImportRunContext;

/**
 * Общий контракт импорта файла с явным контекстом запуска.
 */
interface FileImportInterface
{
    /**
     * Запустить импорт из файла $path на указанном disk. Транспорт (Excel) — в реализации.
     */
    public function import(string $path, ImportRunContext $context, ?string $disk = null): void;
}
