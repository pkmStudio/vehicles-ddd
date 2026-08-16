<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Базовый Laravel Excel adapter для multi-sheet exports Vehicles.
 */
abstract readonly class AbstractMultiSheetExport implements WithMultipleSheets
{
    /**
     * Собирает XLSX-файл и сохраняет его на storage disk.
     *
     * Шаги:
     * 1) Взять disk из аргумента или из config.
     * 2) Собрать storage path из export type prefix и operation id.
     * 3) Сохранить текущий multi-sheet export через Laravel Excel.
     * 4) Вернуть storage path сохраненного файла.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('vehicles.export.output.disk');
        $directory = trim((string) config('vehicles.export.output.directory'), '/');
        $fileName = sprintf('%s-%s.xlsx', $this->exportType()->filePrefix(), $context->operationId);
        $path = $directory !== '' ? sprintf('%s/%s', $directory, $fileName) : $fileName;

        ExcelFacade::store($this, $path, $disk, Excel::XLSX);

        return $path;
    }

    /**
     * Возвращает тип export-файла для имени artifact.
     *
     * Шаги:
     * 1) Вернуть enum concrete export adapter-а.
     */
    abstract protected function exportType(): ExportTypeEnum;

    /**
     * Возвращает список sheet adapters для Laravel Excel.
     *
     * Шаги:
     * 1) Собрать concrete sheet export adapters в порядке Excel workbook.
     *
     * @return array<int, WithTitle>
     */
    abstract public function sheets(): array;
}
