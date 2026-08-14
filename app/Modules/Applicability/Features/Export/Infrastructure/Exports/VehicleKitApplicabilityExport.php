<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class VehicleKitApplicabilityExport implements VehicleKitApplicabilityExportInterface, WithMultipleSheets
{
    /**
     * Получает service, который поставляет данные листов export-файла.
     *
     * Шаги:
     * 1. Сохраняет application service для основного листа и справочников.
     * 2. Оставляет adapter ответственным только за Laravel Excel lifecycle.
     */
    public function __construct(
        private VehicleKitApplicabilityExportServiceInterface $service,
    ) {}

    /**
     * Сохраняет XLSX-файл применяемости комплектов к автомобилям.
     *
     * Шаги:
     * 1. Выбирает disk и директорию из config, если disk не передан явно.
     * 2. Собирает стабильный путь файла с operation id текущего export run.
     * 3. Передает текущий multi-sheet export в Laravel Excel storage.
     * 4. Возвращает путь, который попадет в completion notification.
     */
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
     * Собирает листы XLSX-файла: данные применяемости и справочник кузовов.
     *
     * Шаги:
     * 1. Резолвит основной data sheet через container, чтобы получить его зависимости.
     * 2. Создает reference sheet с headings и rows из export service.
     * 3. Возвращает листы в порядке, который увидит пользователь в workbook.
     *
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
