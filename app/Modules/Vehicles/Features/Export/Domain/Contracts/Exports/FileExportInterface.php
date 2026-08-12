<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;

/**
 * Общий контракт экспорта файла с явным контекстом запуска — симметрично
 * `Import\Domain\Contracts\Imports\External\FileImportInterface`.
 */
interface FileExportInterface
{
    /**
     * Собрать файл и сохранить его на $disk. Транспорт (Excel) — в реализации.
     * Возвращает путь к сохранённому файлу на этом диске.
     *
     * Шаги:
     * 1) Использовать context запуска для имени/метаданных export artifact.
     * 2) Сохранить файл через concrete export adapter.
     * 3) Вернуть storage path сохраненного файла.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string;
}
