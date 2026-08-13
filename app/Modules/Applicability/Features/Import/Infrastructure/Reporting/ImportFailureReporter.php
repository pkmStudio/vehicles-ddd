<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
    /**
     * Сохраняет CSV-отчет ошибок импорта и возвращает путь к файлу.
     *
     * Шаги:
     * 1. Если ошибок нет, возвращает `null` без создания пустого файла.
     * 2. Формирует имя файла отчета с timestamp.
     * 3. Берет disk для отчетов из config фичи.
     * 4. Резолвит `FailuresExportInterface` с текущими failures и сохраняет CSV через Laravel Excel.
     * 5. Возвращает path, который будет отправлен в completion notification.
     */
    public function store(array $failures): ?string
    {
        if ($failures === []) {
            return null;
        }

        $fileName = 'applicability-import-failures-'.now()->format('Y-m-d-His').'.csv';
        $disk = (string) config('applicability.import.failures.disk', 'local');
        $path = sprintf('exports/%s', $fileName);

        ExcelFacade::store(
            export: app()->makeWith(FailuresExportInterface::class, ['failures' => $failures]),
            filePath: $path,
            diskName: $disk,
            writerType: ExcelFormat::CSV,
        );

        return $path;
    }
}
