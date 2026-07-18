<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;

/**
 * Общий порт Excel-адаптера, который создаёт файл Warehouse-экспорта.
 */
interface FileExportInterface
{
    /**
     * Собрать файл и сохранить его на $disk. Возвращает путь к файлу на этом диске.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string;
}
