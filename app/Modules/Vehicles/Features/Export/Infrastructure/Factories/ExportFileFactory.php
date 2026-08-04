<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Factories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Маппит тип экспорта на конкретный Excel-адаптер на Infrastructure boundary.
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
