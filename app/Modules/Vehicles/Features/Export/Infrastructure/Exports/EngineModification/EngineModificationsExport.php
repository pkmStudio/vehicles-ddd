<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\EngineModification;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineModificationsExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\EngineModification\Sheets\EngineModificationSheetExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel adapter export-а связей модификаций и двигателей.
 */
final readonly class EngineModificationsExport extends AbstractMultiSheetExport implements EngineModificationsExportInterface
{
    /**
     * Возвращает тип export-а связей модификаций и двигателей.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::EngineModifications;
    }

    /**
     * Собирает sheet adapters workbook-а связей.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        return [
            app(EngineModificationSheetExport::class),
        ];
    }
}
