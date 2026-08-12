<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Laravel Excel adapter сохранения отчета об ошибках import flow.
 */
final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
    /**
     * Сохранить XLSX report с validation failures.
     *
     * Шаги:
     * 1) Вернуть null, если failures отсутствуют.
     * 2) Собрать имя файла, disk и directory из config.
     * 3) Сохранить FailuresExport через Laravel Excel facade и вернуть path.
     *
     * @param  array<int, mixed>  $failures
     */
    public function store(array $failures): ?string
    {
        if (empty($failures)) {
            return null;
        }

        $fileName = 'import-failures'.now()->format('Y-m-d-His').'.xlsx';
        $disk = (string) config('vehicles.import.failures.disk', 'local');
        $directory = trim((string) config('vehicles.import.failures.directory', 'dan-vehicles/import'), '/');
        $path = $directory !== '' ? sprintf('%s/%s', $directory, $fileName) : $fileName;

        ExcelFacade::store(
            app()->makeWith(FailuresExportInterface::class, ['failures' => $failures]),
            $path,
            $disk,
            Excel::XLSX,
        );

        return $path;
    }
}
