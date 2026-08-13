<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Factories;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;

interface ImportFileFactoryInterface
{
    /**
     * Выбирает import adapter по типу входящего файла.
     *
     * Шаги:
     * 1. Сопоставляет `ImportTypeEnum` с поддерживаемым Excel adapter-ом.
     * 2. Возвращает adapter через общий `FileImportInterface`.
     */
    public function make(ImportTypeEnum $type): FileImportInterface;
}
