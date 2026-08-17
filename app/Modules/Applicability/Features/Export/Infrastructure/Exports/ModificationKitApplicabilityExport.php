<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\ModificationKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\ModificationKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets\ModificationKitApplicabilitySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class ModificationKitApplicabilityExport implements ModificationKitApplicabilityExportInterface, WithMultipleSheets
{
    /**
     * Получает service строк export-а применяемости к модификациям.
     */
    public function __construct(
        private ModificationKitApplicabilityExportServiceInterface $service,
    ) {}

    /**
     * Сохраняет XLSX-файл применяемости к модификациям.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('applicability.export.output.disk', 'local');
        $directory = (string) config('applicability.export.output.directory', 'exports');
        $path = "{$directory}/applicability-modifications-{$context->operationId}.xlsx";

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * Возвращает листы в формате, который принимает импорт `kit_applicability`.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        return [
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Колодки',
            ),
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Масляные фильтры',
                rows: collect(),
            ),
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Воздушные фильтры',
                rows: collect(),
            ),
        ];
    }
}
