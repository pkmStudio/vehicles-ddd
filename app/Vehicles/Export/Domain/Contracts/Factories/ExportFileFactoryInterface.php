<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Factories;

use App\Vehicles\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;

/**
 * Выбирает адаптер экспорта по типу из входящего сообщения.
 */
interface ExportFileFactoryInterface
{
    public function make(ExportTypeEnum $type, bool $isAllow = false): FileExportInterface;
}
