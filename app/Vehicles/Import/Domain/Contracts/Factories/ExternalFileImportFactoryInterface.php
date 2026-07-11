<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Factories;

use App\Vehicles\Import\Domain\Contracts\Imports\External\FileImportInterface;
use App\Vehicles\Import\Domain\Enums\ExternalImportTypeEnum;

/**
 * Выбирает адаптер внешнего файлового импорта по типу из входящего сообщения.
 */
interface ExternalFileImportFactoryInterface
{
    public function make(ExternalImportTypeEnum $type): FileImportInterface;
}
