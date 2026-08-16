<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\ReferenceSheetExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel adapter multi-sheet export-а двигателей.
 */
final readonly class EngineMultiSheetExport extends AbstractMultiSheetExport implements EngineMultiSheetExportInterface
{
    /**
     * Возвращает тип export-а двигателей.
     *
     * Шаги:
     * 1) Вернуть `ExportTypeEnum::Engine` для имени output artifact.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Engine;
    }

    /**
     * Собирает sheet adapters workbook-а двигателей.
     *
     * Шаги:
     * 1) Получить export service из контейнера для справочного листа.
     * 2) Добавить основной лист двигателей.
     * 3) Добавить reference sheet с headings/rows из export service.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        $exportService = app(EngineExportServiceInterface::class);

        return [
            app(EngineMainSheetExport::class),
            // app(EngineSparkPlugsSheetExport::class),
            new ReferenceSheetExport(
                headings: $exportService->getReferenceHeadings(),
                rows: $exportService->getReferenceRows(),
            ),
        ];
    }
}
