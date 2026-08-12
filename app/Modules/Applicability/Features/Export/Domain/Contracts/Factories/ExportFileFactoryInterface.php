<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Factories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;

interface ExportFileFactoryInterface
{
    /**
     * Выбирает export adapter по типу запрошенного файла.
     *
     * Шаги:
     * 1. Сопоставляет `ExportTypeEnum` с поддерживаемым adapter-ом.
     * 2. Возвращает adapter через общий `FileExportInterface`.
     */
    public function make(ExportTypeEnum $type): FileExportInterface;
}
