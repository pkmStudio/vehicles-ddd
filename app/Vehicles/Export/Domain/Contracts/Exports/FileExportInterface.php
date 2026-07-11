<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Exports;

use App\Vehicles\Export\Domain\DTOs\ExportRunContextDTO;

/**
 * Общий контракт экспорта файла с явным контекстом запуска — симметрично
 * `Import\Domain\Contracts\Imports\External\FileImportInterface`.
 */
interface FileExportInterface
{
    /**
     * Собрать файл и сохранить его на $disk. Транспорт (Excel) — в реализации.
     * Возвращает путь к сохранённому файлу на этом диске.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string;
}
