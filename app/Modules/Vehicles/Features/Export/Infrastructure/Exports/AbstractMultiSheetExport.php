<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

abstract readonly class AbstractMultiSheetExport implements WithMultipleSheets
{
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('vehicles.export.output.disk');
        $directory = trim((string) config('vehicles.export.output.directory'), '/');
        $fileName = sprintf('%s-%s.xlsx', $this->exportType()->filePrefix(), $context->operationId);
        $path = $directory !== '' ? sprintf('%s/%s', $directory, $fileName) : $fileName;

        ExcelFacade::store($this, $path, $disk, Excel::XLSX);

        return $path;
    }

    abstract protected function exportType(): ExportTypeEnum;

    abstract public function sheets(): array;
}
