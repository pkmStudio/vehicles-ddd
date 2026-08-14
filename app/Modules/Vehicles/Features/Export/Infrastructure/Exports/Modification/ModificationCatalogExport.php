<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Modification;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\ModificationCatalogExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;

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
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            app(ModificationSheetExport::class),
        ];
    }
}
