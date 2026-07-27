<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ExternalFileImportFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\FileImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;

/**
 * Маппит тип внешнего импорта на конкретный Excel-адаптер.
 */
final readonly class ExternalFileImportFactory implements ExternalFileImportFactoryInterface
{
    public function __construct(
        private VehicleMultiSheetImportInterface $vehicleMultiSheetImport,
        private EngineMultiSheetImportInterface $engineMultiSheetImport,
        private EngineCrossImportInterface $engineCrossImport,
        private EngineSparkPlugSpecificationImportInterface $engineSparkPlugSpecificationImport,
        private ManufacturerImportInterface $manufacturerImport,
    ) {}

    public function make(ExternalImportTypeEnum $type): FileImportInterface
    {
        return match ($type) {
            ExternalImportTypeEnum::VehicleMultiSheet => $this->vehicleMultiSheetImport,
            ExternalImportTypeEnum::EngineMultiSheet => $this->engineMultiSheetImport,
            ExternalImportTypeEnum::EngineCross => $this->engineCrossImport,
            ExternalImportTypeEnum::EngineSparkPlugsByModification => $this->engineSparkPlugSpecificationImport,
            ExternalImportTypeEnum::Manufacturer => $this->manufacturerImport,
        };
    }
}
