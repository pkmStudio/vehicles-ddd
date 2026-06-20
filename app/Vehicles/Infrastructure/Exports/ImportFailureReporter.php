<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports;

use App\Vehicles\Domain\Contracts\Exports\ImportFailureReporterInterface;
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
        ExcelFacade::store(new FailuresExport($failures), $fileName, 'exports', Excel::CSV);

        return "exports/{$fileName}";
    }
}
