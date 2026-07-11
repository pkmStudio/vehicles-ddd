<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Exports\Engine;

use App\Vehicles\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Export\Domain\DTOs\ExportRunContextDTO;
use App\Vehicles\Export\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class EngineMultiSheetExport implements EngineMultiSheetExportInterface, WithMultipleSheets
{
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('vehicles-export.output.disk', 's3');
        $path = sprintf('engine-catalog-%s.xlsx', $context->runId);

        ExcelFacade::store($this, $path, $disk, Excel::XLSX);

        return $path;
    }

    public function sheets(): array
    {
        return [
            app(EngineMainSheetExport::class),
            app(EngineSparkPlugsSheetExport::class),
        ];
    }
}
