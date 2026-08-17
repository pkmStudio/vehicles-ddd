<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Manufacturer;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\ManufacturerExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Manufacturer\Sheets\ManufacturerSheetExport;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Laravel Excel adapter экспорта производителей автомобилей.
 */
final readonly class ManufacturerExport extends AbstractMultiSheetExport implements ManufacturerExportInterface
{
    /**
     * Возвращает тип export-а производителей.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Manufacturer;
    }

    /**
     * Собирает workbook производителей.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        return [
            app(ManufacturerSheetExport::class),
        ];
    }
}
