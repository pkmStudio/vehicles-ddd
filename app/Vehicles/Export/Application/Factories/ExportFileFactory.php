<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Factories;

use App\Vehicles\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;

/**
 * Маппит тип экспорта на конкретный Excel-адаптер. $isAllow — бизнес-фильтр,
 * применим только к Vehicle (см. VehicleMultiSheetExport); резолвится через
 * makeWith, а не конструкторную инъекцию, потому что это runtime-параметр
 * конкретного запроса, а не постоянная зависимость (см. sub-sheet'ы Import).
 */
final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    public function make(ExportTypeEnum $type, bool $isAllow = false): FileExportInterface
    {
        return match ($type) {
            ExportTypeEnum::Vehicle => app()->makeWith(VehicleMultiSheetExportInterface::class, ['isAllow' => $isAllow]),
            ExportTypeEnum::Engine => app(EngineMultiSheetExportInterface::class),
        };
    }
}
