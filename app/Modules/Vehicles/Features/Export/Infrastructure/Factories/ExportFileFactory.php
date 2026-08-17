<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Factories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineModificationsExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\ManufacturerExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\ModificationCatalogExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleCountryCsvExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleFullCsvExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Маппит тип экспорта на конкретный Excel-адаптер на Infrastructure boundary.
 */
final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    /**
     * Создать Excel-export адаптер для указанного типа выгрузки.
     *
     * Шаги:
     * - Сопоставить enum типа экспорта с concrete Laravel Excel export.
     * - Передать флаг разрешенных записей в vehicle export через container parameters.
     * - Вернуть реализацию общего файлового export-контракта.
     */
    public function make(ExportTypeEnum $type, bool $isAllow = false): FileExportInterface
    {
        return match ($type) {
            ExportTypeEnum::Vehicle => app()->makeWith(VehicleMultiSheetExportInterface::class, ['isAllow' => $isAllow]),
            ExportTypeEnum::Engine => app(EngineMultiSheetExportInterface::class),
            ExportTypeEnum::Manufacturer => app(ManufacturerExportInterface::class),
            ExportTypeEnum::ModificationCatalog => app(ModificationCatalogExportInterface::class),
            ExportTypeEnum::EngineModifications => app(EngineModificationsExportInterface::class),
            ExportTypeEnum::VehicleFullCsv => app(VehicleFullCsvExportInterface::class),
            ExportTypeEnum::VehicleCountryCsv => app(VehicleCountryCsvExportInterface::class),
        };
    }
}
