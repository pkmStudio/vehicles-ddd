<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class ImportFailureReporter implements ImportFailureReporterInterface
{
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
