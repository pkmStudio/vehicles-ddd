<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Reporting;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Сохраняет накопленные ошибки Warehouse-импорта в CSV-отчёт на диске.
 */
final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
    /**
     * Этот метод сохраняет failures в CSV, если они есть, и возвращает путь к файлу.
     * Имя файла содержит $type->value — чтобы по пути было сразу видно, из какого импорта отчёт.
     *
     * Шаги:
     * 1) Вернуть null, если failures пустые.
     * 2) Собрать имя CSV-файла из типа импорта и текущего времени.
     * 3) Получить disk отчётов из warehouse config.
     * 4) Создать FailuresExport через container с failures и type.
     * 5) Сохранить CSV через Laravel Excel и вернуть путь.
     *
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function store(array $failures, ImportTypeEnum $type): ?string
    {
        if ($failures === []) {
            return null;
        }

        $fileName = sprintf('warehouse-import-failures-%s-%s.csv', $type->value, now()->format('Y-m-d-His'));
        $disk = (string) config(
            key: 'warehouse.import.failures.disk',
            default: 'local',
        );
        $path = sprintf('exports/%s', $fileName);
        $export = app()->makeWith(
            abstract: FailuresExportInterface::class,
            parameters: [
                'failures' => $failures,
                'type' => $type,
            ],
        );

        ExcelFacade::store(
            export: $export,
            filePath: $path,
            diskName: $disk,
            writerType: ExcelFormat::CSV,
        );

        return $path;
    }
}
