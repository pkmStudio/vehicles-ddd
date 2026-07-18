<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Exports;

use App\Vehicles\Export\Domain\DTOs\ExportRunContextDTO;
use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

abstract readonly class AbstractMultiSheetExport implements WithMultipleSheets
{
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('vehicles.export.output.disk', 'local');
        $directory = (string) config('vehicles.export.output.directory', 'exports');
        $path = sprintf('%s/%s-%s.xlsx', $directory, $this->exportType()->filePrefix(), $context->runId);

        ExcelFacade::store($this, $path, $disk, Excel::XLSX);

        return $path;
    }

    abstract protected function exportType(): ExportTypeEnum;

    abstract public function sheets(): array;
}
