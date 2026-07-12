<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Exports;

use App\Warehouse\Export\Domain\DTOs\ExportRunContextDTO;

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
