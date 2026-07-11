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
use App\Vehicles\Export\Application\Services\External\ExportRunCacheService;
use App\Vehicles\Export\Application\Services\External\CleanupStaleExportFilesService;
use App\Vehicles\Export\Application\Factories\ExportFileFactory;
use App\Vehicles\Export\Application\UseCases\External\StartExportUseCase;
use App\Vehicles\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Details\ExportDetailsBuilderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Rows\VehicleExportRowInterface;
use App\Vehicles\Export\Domain\Contracts\Services\Expanders\WiperRowExpanderInterface;
use App\Vehicles\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Vehicles\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Vehicles\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Vehicles\Export\Infrastructure\Exports\Engine\EngineMultiSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Vehicle\VehicleMultiSheetExport;
use App\Vehicles\Export\Infrastructure\Notifications\RabbitMqExportNotificationService;
use App\Vehicles\Export\Infrastructure\Repositories\EngineRepository;
use App\Vehicles\Export\Infrastructure\Repositories\VehicleRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Export (интерфейс → реализация).
 * Repository (read) — своя копия поверх Export\Infrastructure\Models, работает через
 * <Entity>Data (spatie/laravel-data, plan.md §3), как и Import. Общий VehiclesServiceProvider
 * с этого момента больше никем не используется.
 */
final class ExportServiceProvider extends ServiceProvider
{
    private const array EXPORT_BINDINGS = [
        EngineMultiSheetExportInterface::class => EngineMultiSheetExport::class,
        VehicleMultiSheetExportInterface::class => VehicleMultiSheetExport::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        VehicleRepositoryInterface::class => VehicleRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        ExportDetailsBuilderInterface::class => ExportDetailsBuilder::class,
        PartSpecificationRowExpanderInterface::class => PartSpecificationRowExpander::class,
        VehicleExportRowInterface::class => VehicleExportRow::class,
        EngineExportRowInterface::class => EngineExportRow::class,
        WiperRowExpanderInterface::class => WiperRowExpander::class,
        EngineExportServiceInterface::class => EngineExportService::class,
        VehicleExportServiceInterface::class => VehicleExportService::class,
        ExportRunCacheServiceInterface::class => ExportRunCacheService::class,
        CleanupStaleExportFilesServiceInterface::class => CleanupStaleExportFilesService::class,
    ];

    private const array FACTORY_BINDINGS = [
        ExportFileFactoryInterface::class => ExportFileFactory::class,
    ];

    private const array USE_CASE_BINDINGS = [
        StartExportUseCaseInterface::class => StartExportUseCase::class,
    ];

    public function register(): void
    {
        // Уведомление о готовом файле экспорта уходит в RabbitMQ (FILE_EXPORTED).
        $this->app->bind(ExportNotificationServiceInterface::class, RabbitMqExportNotificationService::class);

        foreach (self::EXPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::USE_CASE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
