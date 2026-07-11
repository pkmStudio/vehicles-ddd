<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Reporting;

use App\Vehicles\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Vehicles\Import\Domain\Contracts\Reporting\FailuresExportInterface;
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
        $disk = (string) config('vehicles-import.failures.disk', 'local');
        $path = sprintf('exports/%s', $fileName);

        ExcelFacade::store(
            app()->makeWith(FailuresExportInterface::class, ['failures' => $failures]),
            $path,
            $disk,
            Excel::CSV,
        );

        return $path;
    }
}
