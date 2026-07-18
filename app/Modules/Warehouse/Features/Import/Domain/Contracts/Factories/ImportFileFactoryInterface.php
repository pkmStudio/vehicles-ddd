<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Порт selector-фабрики, выбирающей адаптер Warehouse-импорта по enum-типу.
 */
interface ImportFileFactoryInterface
{
    /**
     * Возвращает адаптер импорта для типа запроса.
     */
    public function make(ImportTypeEnum $type): FileImportInterface;
}
