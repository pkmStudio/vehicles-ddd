<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Modification;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\ModificationCatalogExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Modification\Sheets\ModificationSheetExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel adapter export-а каталога модификаций.
 */
final readonly class ModificationCatalogExport extends AbstractMultiSheetExport implements ModificationCatalogExportInterface
{
    /**
     * Возвращает тип export-а модификаций.
     *
     * Шаги:
     * 1) Вернуть `ExportTypeEnum::ModificationCatalog` для имени output artifact.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::ModificationCatalog;
    }

    /**
     * Собирает sheet adapters workbook-а модификаций.
     *
     * Шаги:
     * 1) Добавить единственный лист каталога модификаций.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        return [
            app(ModificationSheetExport::class),
        ];
    }
}
