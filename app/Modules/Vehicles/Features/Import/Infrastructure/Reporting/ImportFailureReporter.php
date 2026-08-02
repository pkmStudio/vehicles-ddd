<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
    public function store(array $failures): ?string
    {
        if (empty($failures)) {
            return null;
        }

        $fileName = 'import-failures'.now()->format('Y-m-d-His').'.csv';
        $disk = (string) config('vehicles.import.failures.disk', 'local');
        $directory = trim((string) config('vehicles.import.failures.directory', 'dan-vehicles/import'), '/');
        $path = $directory !== '' ? sprintf('%s/%s', $directory, $fileName) : $fileName;

        ExcelFacade::store(
            app()->makeWith(FailuresExportInterface::class, ['failures' => $failures]),
            $path,
            $disk,
            Excel::CSV,
        );

        return $path;
    }
}
