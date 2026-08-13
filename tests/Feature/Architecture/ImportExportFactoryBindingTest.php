<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum as ApplicabilityExportTypeEnum;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum as ApplicabilityImportTypeEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum as VehiclesExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Factories\ExportFileFactory;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\PackDimensionExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportTypeEnum as WarehouseExportTypeEnum;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum as WarehouseImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Infrastructure\Factories\ImportFileFactory;
use Tests\TestCase;

final class ImportExportFactoryBindingTest extends TestCase
{
    public function test_factory_ports_resolve_to_infrastructure_implementations(): void
    {
        $expected = [
            ExportFileFactoryInterface::class => ExportFileFactory::class,
            ImportFileFactoryInterface::class => ImportFileFactory::class,
            \App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface::class => \App\Modules\Warehouse\Features\Export\Infrastructure\Factories\ExportFileFactory::class,
            \App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface::class => \App\Modules\Applicability\Features\Import\Infrastructure\Factories\ImportFileFactory::class,
            \App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface::class => \App\Modules\Applicability\Features\Export\Infrastructure\Factories\ExportFileFactory::class,
        ];

        foreach ($expected as $port => $implementation) {
            $this->assertInstanceOf($implementation, app($port));
        }
    }

    public function test_import_factories_return_expected_adapters(): void
    {
        $warehouse = app(ImportFileFactoryInterface::class);
        $applicability = app(\App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface::class);

        $this->assertInstanceOf(NomenclatureImportInterface::class, $warehouse->make(WarehouseImportTypeEnum::Nomenclature));
        $this->assertInstanceOf(PackDimensionImportInterface::class, $warehouse->make(WarehouseImportTypeEnum::PackDimension));
        $this->assertInstanceOf(KitImportInterface::class, $warehouse->make(WarehouseImportTypeEnum::Kit));
        $this->assertInstanceOf(
            KitApplicabilityImportInterface::class,
            $applicability->make(ApplicabilityImportTypeEnum::KitApplicability),
        );
    }

    public function test_export_factories_return_expected_adapters(): void
    {
        $vehicles = app(ExportFileFactoryInterface::class);
        $warehouse = app(\App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface::class);
        $applicability = app(\App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface::class);

        $this->assertInstanceOf(VehicleMultiSheetExportInterface::class, $vehicles->make(VehiclesExportTypeEnum::Vehicle));
        $this->assertInstanceOf(EngineMultiSheetExportInterface::class, $vehicles->make(VehiclesExportTypeEnum::Engine));
        $this->assertInstanceOf(
            NomenclatureByTypeExportInterface::class,
            $warehouse->make(WarehouseExportTypeEnum::NomenclatureByType, typeId: 1),
        );
        $this->assertInstanceOf(PackDimensionExportInterface::class, $warehouse->make(WarehouseExportTypeEnum::PackDimension));
        $this->assertInstanceOf(KitExportInterface::class, $warehouse->make(WarehouseExportTypeEnum::Kit));
        $this->assertInstanceOf(
            WiperAdapterAuditExportInterface::class,
            $warehouse->make(WarehouseExportTypeEnum::WiperAdapterAudit),
        );
        $this->assertInstanceOf(
            VehicleKitApplicabilityExportInterface::class,
            $applicability->make(ApplicabilityExportTypeEnum::VehicleKitApplicability),
        );
    }
}
