<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class CalculationFailureReporter implements CalculationFailureReporterInterface
{
    public function store(KitApplicabilityCalculationResultDTO $result): ?string
    {
        if ($result->errors === []) {
            return null;
        }

        $fileName = sprintf(
            'applicability-calculation-failures-%s-%s.csv',
            $result->runId,
            now()->format('Y-m-d-His'),
        );
        $disk = (string) config('applicability.calculation.failures.disk', 'local');
        $directory = trim((string) config('applicability.calculation.failures.directory', 'exports'), '/');
        $path = "{$directory}/{$fileName}";

        ExcelFacade::store(
            export: new CalculationFailuresExport($result),
            filePath: $path,
            diskName: $disk,
            writerType: ExcelFormat::CSV,
        );

        return $path;
    }
}
