<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Выбирает адаптер экспорта по типу из входящего сообщения.
 */
interface ExportFileFactoryInterface
{
    public function make(ExportTypeEnum $type, bool $isAllow = false): FileExportInterface;
}
