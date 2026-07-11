<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Providers;

use App\Vehicles\Import\Application\Factories\Engine\EngineDataFactory;
use App\Vehicles\Import\Application\Factories\External\ExternalFileImportFactory;
use App\Vehicles\Import\Application\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Import\Application\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Import\Application\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Import\Application\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Import\Application\Services\External\CleanupExternalImportFileService;
use App\Vehicles\Import\Application\Services\External\ExternalImportCacheService;
use App\Vehicles\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Vehicles\Import\Application\Services\Engine\AssignEngineGroupService;
use App\Vehicles\Import\Application\Services\Engine\EngineEditableColumnsMapper;
use App\Vehicles\Import\Application\Services\Engine\UpdateEngineEditableFieldsService;
use App\Vehicles\Import\Application\Services\Engine\UpsertEngineFromSheetService;
use App\Vehicles\Import\Application\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Vehicles\Import\Application\Services\Engine\UpsertSparkPlugSpecByModificationService;
use App\Vehicles\Import\Application\Services\EngineModification\LinkEngineModificationFromRowService;
use App\Vehicles\Import\Application\Services\EngineModificationReadinessGate;
use App\Vehicles\Import\Application\Services\Manufacturer\UpsertManufacturerFromRowService;
use App\Vehicles\Import\Application\Services\Modification\UpsertModificationFromRowService;
use App\Vehicles\Import\Application\Services\Reporting\ReportImportResultService;
use App\Vehicles\Import\Application\Services\Template\DetailsBuilder;
use App\Vehicles\Import\Application\Services\Template\TemplateDataBuilder;
use App\Vehicles\Import\Application\Services\Vehicle\UpsertVehicleFromSheetService;
use App\Vehicles\Import\Application\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Vehicles\Import\Application\Services\Vehicle\VehicleWiperSpecificationImportService;
use App\Vehicles\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ExternalFileImportFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Vehicles\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Vehicles\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Template\DetailsBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\EngineEditableColumnsMapperInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpdateEngineEditableFieldsServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\FeatureCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\FeatureValueCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Exports\FailuresExportInterface;
use App\Vehicles\Import\Domain\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineCommandImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineCrossImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineModificationImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EnginesCodeImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\ManufacturerCommandImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\ModificationCommandImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\VehicleCommandImportInterface;
use App\Vehicles\Import\Domain\Contracts\Imports\VehicleMultiSheetImportInterface;
use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Import\Infrastructure\Commands\EngineCommand;
use App\Vehicles\Import\Infrastructure\Commands\EngineModificationCommand;
use App\Vehicles\Import\Infrastructure\Commands\FeatureCommand;
use App\Vehicles\Import\Infrastructure\Commands\FeatureValueCommand;
use App\Vehicles\Import\Infrastructure\Commands\ManufacturerCommand;
use App\Vehicles\Import\Infrastructure\Commands\ModificationCommand;
use App\Vehicles\Import\Infrastructure\Commands\PartSpecificationCommand;
use App\Vehicles\Import\Infrastructure\Commands\VehicleCommand;
use App\Vehicles\Import\Infrastructure\Repositories\EngineRepository;
use App\Vehicles\Import\Infrastructure\Repositories\FeatureRepository;
use App\Vehicles\Import\Infrastructure\Repositories\FeatureValueRepository;
use App\Vehicles\Import\Infrastructure\Repositories\ManufacturerRepository;
use App\Vehicles\Import\Infrastructure\Repositories\ModificationRepository;
use App\Vehicles\Import\Infrastructure\Repositories\PartSpecificationRepository;
use App\Vehicles\Import\Infrastructure\Repositories\VehicleRepository;
use App\Vehicles\Import\Infrastructure\Exports\FailuresExport;
use App\Vehicles\Import\Infrastructure\Exports\ImportFailureReporter;
use App\Vehicles\Import\Infrastructure\Imports\Engine\EngineCommandImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\EngineCrossImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\EngineMultiSheetImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\EnginesCodeImport;
use App\Vehicles\Import\Infrastructure\Imports\Engine\EngineSparkPlugSpecificationImport;
use App\Vehicles\Import\Infrastructure\Imports\EngineModification\EngineModificationImport;
use App\Vehicles\Import\Infrastructure\Imports\Manufacturer\ManufacturerCommandImport;
use App\Vehicles\Import\Infrastructure\Imports\Modification\ModificationCommandImport;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\VehicleCommandImport;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\VehicleMultiSheetImport;
use App\Vehicles\Import\Infrastructure\Notifications\RabbitMqFileNotificationService;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Import (интерфейс → реализация).
 * Repository (read) и Command (write) — обе свои, поверх Import\Infrastructure\Models и
 * работают через <Entity>Data (spatie/laravel-data, plan.md §3). Export пока читает через
 * отдельный, старый VehiclesServiceProvider — его собственный переезд на Data ещё впереди.
 */
final class ImportServiceProvider extends ServiceProvider
{
    private const array USE_CASE_BINDINGS = [
        StartExternalFileImportUseCaseInterface::class => StartExternalFileImportUseCase::class,
    ];

    private const array COMMAND_BINDINGS = [
        ManufacturerCommandInterface::class => ManufacturerCommand::class,
        VehicleCommandInterface::class => VehicleCommand::class,
        ModificationCommandInterface::class => ModificationCommand::class,
        EngineCommandInterface::class => EngineCommand::class,
        EngineModificationCommandInterface::class => EngineModificationCommand::class,
        PartSpecificationCommandInterface::class => PartSpecificationCommand::class,
        FeatureCommandInterface::class => FeatureCommand::class,
        FeatureValueCommandInterface::class => FeatureValueCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        VehicleRepositoryInterface::class => VehicleRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
        FeatureRepositoryInterface::class => FeatureRepository::class,
        FeatureValueRepositoryInterface::class => FeatureValueRepository::class,
    ];

    /**
     * Поведенческие порты импорта: import(path) / parse(path); Excel — внутри реализации.
     */
    private const array IMPORT_BINDINGS = [
        ManufacturerCommandImportInterface::class => ManufacturerCommandImport::class,
        VehicleCommandImportInterface::class => VehicleCommandImport::class,
        EngineCommandImportInterface::class => EngineCommandImport::class,
        ModificationCommandImportInterface::class => ModificationCommandImport::class,
        EngineModificationImportInterface::class => EngineModificationImport::class,
        VehicleMultiSheetImportInterface::class => VehicleMultiSheetImport::class,
        EngineMultiSheetImportInterface::class => EngineMultiSheetImport::class,
        EngineCrossImportInterface::class => EngineCrossImport::class,
        EngineSparkPlugSpecificationImportInterface::class => EngineSparkPlugSpecificationImport::class,
        EnginesCodeImportInterface::class => EnginesCodeImport::class,
    ];

    private const array IMPORT_SHEET_BINDINGS = [
        FailuresExportInterface::class => FailuresExport::class,
    ];

    private const array FACTORY_BINDINGS = [
        EngineDataFactoryInterface::class => EngineDataFactory::class,
        ExternalFileImportFactoryInterface::class => ExternalFileImportFactory::class,
        VehicleDataFactoryInterface::class => VehicleDataFactory::class,
        ModificationDataFactoryInterface::class => ModificationDataFactory::class,
        ManufacturerDataFactoryInterface::class => ManufacturerDataFactory::class,
        EngineModificationDataFactoryInterface::class => EngineModificationDataFactory::class,
    ];

    private const array SERVICE_BINDINGS = [
        CleanupExternalImportFileServiceInterface::class => CleanupExternalImportFileService::class,
        ExternalImportCacheServiceInterface::class => ExternalImportCacheService::class,
        TemplateDataBuilderInterface::class => TemplateDataBuilder::class,
        DetailsBuilderInterface::class => DetailsBuilder::class,
        EngineEditableColumnsMapperInterface::class => EngineEditableColumnsMapper::class,
        EngineModificationReadinessGateInterface::class => EngineModificationReadinessGate::class,
        ReportImportResultServiceInterface::class => ReportImportResultService::class,
        UpsertEngineFromSheetServiceInterface::class => UpsertEngineFromSheetService::class,
        UpsertEngineSparkPlugSpecServiceInterface::class => UpsertEngineSparkPlugSpecService::class,
        UpsertSparkPlugSpecByModificationServiceInterface::class => UpsertSparkPlugSpecByModificationService::class,
        AssignEngineGroupServiceInterface::class => AssignEngineGroupService::class,
        UpdateEngineEditableFieldsServiceInterface::class => UpdateEngineEditableFieldsService::class,
        UpsertManufacturerFromRowServiceInterface::class => UpsertManufacturerFromRowService::class,
        UpsertModificationFromRowServiceInterface::class => UpsertModificationFromRowService::class,
        UpsertVehicleFromSheetServiceInterface::class => UpsertVehicleFromSheetService::class,
        UpsertVehicleFromTdRowServiceInterface::class => UpsertVehicleFromTdRowService::class,
        VehicleWiperSpecificationImportServiceInterface::class => VehicleWiperSpecificationImportService::class,
        LinkEngineModificationFromRowServiceInterface::class => LinkEngineModificationFromRowService::class,
    ];

    public function register(): void
    {
        // Уведомление о готовом файле уходит в RabbitMQ (сервису с Filament).
        $this->app->bind(FileNotificationServiceInterface::class, RabbitMqFileNotificationService::class);

        // Выгрузка отчёта об ошибках импорта (Excel → S3).
        $this->app->bind(ImportFailureReporterInterface::class, ImportFailureReporter::class);

        foreach (self::USE_CASE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::IMPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::IMPORT_SHEET_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
