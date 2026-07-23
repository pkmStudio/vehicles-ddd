<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class VehicleKitApplicabilityExport implements FromCollection, VehicleKitApplicabilityExportInterface, WithHeadings, WithMapping
{
    public function __construct(
        private VehicleKitApplicabilityExportServiceInterface $service,
    ) {}

    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('applicability.export.output.disk', 'local');
        $directory = (string) config('applicability.export.output.directory', 'exports');
        $path = "{$directory}/applicability-vehicles-{$context->runId}.xlsx";

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    public function collection(): Collection
    {
        return $this->service->getRows();
    }

    public function map($row): array
    {
        return $this->service->mapRow($row);
    }

    public function headings(): array
    {
        return $this->service->getHeadings();
    }
}
