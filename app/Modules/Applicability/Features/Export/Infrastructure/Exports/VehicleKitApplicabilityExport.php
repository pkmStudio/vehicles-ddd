<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets\VehicleKitApplicabilityDataSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class VehicleKitApplicabilityExport implements VehicleKitApplicabilityExportInterface, WithMultipleSheets
{
    public function __construct(
        private VehicleKitApplicabilityExportServiceInterface $service,
    ) {}

    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('applicability.export.output.disk', 'local');
        $directory = (string) config('applicability.export.output.directory', 'exports');
        $path = "{$directory}/applicability-vehicles-{$context->operationId}.xlsx";

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            app(VehicleKitApplicabilityDataSheetExport::class),
            new ReferenceSheetExport(
                headings: $this->service->getReferenceHeadings(),
                rows: $this->service->getReferenceRows(),
            ),
        ];
    }
}
