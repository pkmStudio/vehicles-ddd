<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\FileImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;

/**
 * Выбирает адаптер внешнего файлового импорта по типу из входящего сообщения.
 */
interface ExternalFileImportFactoryInterface
{
    /**
     * Выбрать file import adapter по external import type.
     *
     * Шаги:
     * 1) Сопоставить ExternalImportTypeEnum с concrete import contract.
     * 2) Вернуть общий FileImportInterface для запуска use case.
     */
    public function make(ExternalImportTypeEnum $type): FileImportInterface;
}
