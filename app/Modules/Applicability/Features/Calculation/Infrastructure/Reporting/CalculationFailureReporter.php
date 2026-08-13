<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class CalculationFailureReporter implements CalculationFailureReporterInterface
{
    /**
     * Сохраняет CSV-отчет по ошибкам расчета применяемости.
     *
     * Шаги:
     * 1. Возвращает `null`, если aggregate result не содержит ошибок.
     * 2. Формирует имя файла по operation id и текущему времени.
     * 3. Берет disk и directory из конфигурации calculation failures.
     * 4. Сохраняет `CalculationFailuresExport` через Laravel Excel и возвращает path.
     */
    public function store(KitApplicabilityCalculationResultDTO $result): ?string
    {
        if ($result->errors === []) {
            return null;
        }

        $fileName = sprintf(
            'applicability-calculation-failures-%s-%s.csv',
            $result->operationId,
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
