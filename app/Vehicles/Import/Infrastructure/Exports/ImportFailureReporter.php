<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Exports;

use App\Vehicles\Import\Domain\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Import\Domain\Contracts\Exports\FailuresExportInterface;
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

        // TODO: диск 'exports' должен указывать на общее S3-хранилище (config/filesystems.php).
        ExcelFacade::store(
            app()->makeWith(FailuresExportInterface::class, ['failures' => $failures]),
            $fileName,
            'exports',
            Excel::CSV,
        );

        return "exports/{$fileName}";
    }
}
