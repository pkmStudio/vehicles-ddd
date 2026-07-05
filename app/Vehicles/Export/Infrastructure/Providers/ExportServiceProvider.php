<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Providers;

use App\Vehicles\Export\Application\Services\EngineExportService;
use App\Vehicles\Export\Application\Services\VehicleExportService;
use App\Vehicles\Export\Application\Services\Rows\EngineExportRow;
use App\Vehicles\Export\Application\Services\Details\ExportDetailsBuilder;
use App\Vehicles\Export\Application\Services\Expanders\PartSpecificationRowExpander;
use App\Vehicles\Export\Application\Services\Rows\VehicleExportRow;
use App\Vehicles\Export\Application\Services\Expanders\WiperRowExpander;
use App\Vehicles\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Details\ExportDetailsBuilderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\VehicleExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Export\Infrastructure\Exports\Engine\EngineMultiSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\VehicleMultiSheetExport;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Export (интерфейс → реализация).
 * Repository-биндинги (read) пока остаются в корневом VehiclesServiceProvider поверх общих
 * Domain\Models — Import уже переехал на свою копию модели + Data (plan.md §3), у Export этот
 * переезд ещё впереди отдельным заходом.
 */
final class ExportServiceProvider extends ServiceProvider
{
    private const array EXPORT_BINDINGS = [
        EngineMultiSheetExportInterface::class => EngineMultiSheetExport::class,
        VehicleMultiSheetExportInterface::class => VehicleMultiSheetExport::class,
    ];

    private const array SERVICE_BINDINGS = [
        ExportDetailsBuilderInterface::class => ExportDetailsBuilder::class,
        PartSpecificationRowExpanderInterface::class => PartSpecificationRowExpander::class,
        VehicleExportRowInterface::class => VehicleExportRow::class,
        EngineExportRowInterface::class => EngineExportRow::class,
        WiperRowExpanderInterface::class => WiperRowExpander::class,
        EngineExportServiceInterface::class => EngineExportService::class,
        VehicleExportServiceInterface::class => VehicleExportService::class,
    ];

    public function register(): void
    {
        foreach (self::EXPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
