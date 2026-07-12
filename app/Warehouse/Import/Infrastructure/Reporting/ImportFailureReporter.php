<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Reporting;

use App\Warehouse\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Warehouse\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Сохраняет накопленные ошибки Warehouse-импорта в CSV-отчёт на диске.
 */
final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
    /**
     * Этот метод сохраняет failures в CSV, если они есть, и возвращает путь к файлу.
     *
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function store(array $failures): ?string
    {
        if ($failures === []) {
            return null;
        }

        $fileName = 'warehouse-import-failures-'.now()->format('Y-m-d-His').'.csv';
        $disk = (string) config(
            key: 'warehouse.import.failures.disk',
            default: 'local',
        );
        $path = sprintf('exports/%s', $fileName);
        $export = app()->makeWith(
            abstract: FailuresExportInterface::class,
            parameters: [
                'failures' => $failures,
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
