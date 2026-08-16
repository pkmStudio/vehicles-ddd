<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Import\Application\Factories\EngineDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\EngineModificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\ExternalFileImportFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\ManufacturerDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\ModificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\PartSpecificationDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Factories\VehicleDataFactory;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\AssignEngineGroupService;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertEngineFromRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Modules\Vehicles\Features\Import\Application\Services\Engine\UpsertSparkPlugSpecByModificationService;
use App\Modules\Vehicles\Features\Import\Application\Services\EngineModification\LinkEngineModificationFromRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\External\CleanupExternalImportFileService;
use App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer\UpsertManufacturerFromSheetService;
use App\Modules\Vehicles\Features\Import\Application\Services\Manufacturer\UpsertManufacturerFromTdRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Modification\UpsertModificationFromSheetService;
use App\Modules\Vehicles\Features\Import\Application\Services\Modification\UpsertModificationFromTdRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Reporting\ReportImportResultService;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleFromRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Modules\Vehicles\Features\Import\Application\Services\Vehicle\UpsertVehicleWiperSpecificationFromRowService;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\EngineModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ExternalFileImportFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ModificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\VehicleDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Files\ImportFileStorageInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Files\LocalImportFileStorageInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EnginesCodeImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineCrossImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineModificationsImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\EngineSparkPlugSpecificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ManufacturerImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\ModificationCatalogImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\AssignEngineGroupServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer\UpsertManufacturerFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification\UpsertModificationFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleWiperSpecificationFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Cache\LaravelExternalImportCacheService;
use App\Modules\Vehicles\Features\Import\Infrastructure\Cache\LaravelImportFailureStore;
use App\Modules\Vehicles\Features\Import\Infrastructure\Clients\TemplatesClient;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\EngineCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\EngineModificationCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\ManufacturerCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\ModificationCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\PartSpecificationCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Commands\VehicleCommand;
use App\Modules\Vehicles\Features\Import\Infrastructure\Files\LaravelImportFileStorage;
use App\Modules\Vehicles\Features\Import\Infrastructure\Files\LaravelLocalImportFileStorage;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineCommandImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineCrossImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineMultiSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EnginesCodeImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\EngineSparkPlugSpecificationImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\EngineModificationImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\EngineModificationsImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\ManufacturerCommandImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\ManufacturerImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\ModificationCatalogImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Modification\ModificationCommandImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\VehicleCommandImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\VehicleMultiSheetImport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Notifications\RabbitMqFileNotificationService;
use App\Modules\Vehicles\Features\Import\Infrastructure\Publishers\RabbitMqLocalImportRequestPublisher;
use App\Modules\Vehicles\Features\Import\Infrastructure\Reporting\FailuresExport;
use App\Modules\Vehicles\Features\Import\Infrastructure\Reporting\ImportFailureReporter;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\EngineRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\FeatureValueRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\ManufacturerRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\ModificationRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\PartSpecificationRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Repositories\VehicleRepository;
use App\Modules\Vehicles\Features\Import\Infrastructure\Services\LaravelEngineModificationReadinessGate;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Import (интерфейс → реализация).
 * Repository (read) и Command (write) — обе свои, поверх Import\Infrastructure\Models и
 * работают через <Entity>Data (spatie/laravel-data, plan.md §3). Export пока читает через
 * отдельный, старый VehiclesServiceProvider — его собственный переезд на Data ещё впереди.
 */
final class ImportServiceProvider extends ServiceProvider
{
    private const array COMMAND_BINDINGS = [
        ManufacturerCommandInterface::class => ManufacturerCommand::class,
        VehicleCommandInterface::class => VehicleCommand::class,
        ModificationCommandInterface::class => ModificationCommand::class,
        EngineCommandInterface::class => EngineCommand::class,
        EngineModificationCommandInterface::class => EngineModificationCommand::class,
        PartSpecificationCommandInterface::class => PartSpecificationCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        VehicleRepositoryInterface::class => VehicleRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
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
        ManufacturerImportInterface::class => ManufacturerImport::class,
        ModificationCatalogImportInterface::class => ModificationCatalogImport::class,
        EngineModificationsImportInterface::class => EngineModificationsImport::class,
    ];

    private const array IMPORT_SHEET_BINDINGS = [
        FailuresExportInterface::class => FailuresExport::class,
        ImportFailureStoreInterface::class => LaravelImportFailureStore::class,
    ];

    private const array FACTORY_BINDINGS = [
        EngineDataFactoryInterface::class => EngineDataFactory::class,
        ExternalFileImportFactoryInterface::class => ExternalFileImportFactory::class,
        VehicleDataFactoryInterface::class => VehicleDataFactory::class,
        ModificationDataFactoryInterface::class => ModificationDataFactory::class,
        ManufacturerDataFactoryInterface::class => ManufacturerDataFactory::class,
        EngineModificationDataFactoryInterface::class => EngineModificationDataFactory::class,
        PartSpecificationDataFactoryInterface::class => PartSpecificationDataFactory::class,
    ];

    private const array SERVICE_BINDINGS = [
        CleanupExternalImportFileServiceInterface::class => CleanupExternalImportFileService::class,
        ExternalImportCacheServiceInterface::class => LaravelExternalImportCacheService::class,
        EngineModificationReadinessGateInterface::class => LaravelEngineModificationReadinessGate::class,
        ReportImportResultServiceInterface::class => ReportImportResultService::class,
        UpsertEngineFromRowServiceInterface::class => UpsertEngineFromRowService::class,
        UpsertEngineSparkPlugSpecServiceInterface::class => UpsertEngineSparkPlugSpecService::class,
        UpsertSparkPlugSpecByModificationServiceInterface::class => UpsertSparkPlugSpecByModificationService::class,
        AssignEngineGroupServiceInterface::class => AssignEngineGroupService::class,
        UpsertManufacturerFromTdRowServiceInterface::class => UpsertManufacturerFromTdRowService::class,
        UpsertManufacturerFromSheetServiceInterface::class => UpsertManufacturerFromSheetService::class,
        UpsertModificationFromSheetServiceInterface::class => UpsertModificationFromSheetService::class,
        UpsertModificationFromTdRowServiceInterface::class => UpsertModificationFromTdRowService::class,
        UpsertVehicleFromRowServiceInterface::class => UpsertVehicleFromRowService::class,
        UpsertVehicleFromTdRowServiceInterface::class => UpsertVehicleFromTdRowService::class,
        UpsertVehicleWiperSpecificationFromRowServiceInterface::class => UpsertVehicleWiperSpecificationFromRowService::class,
        LinkEngineModificationFromRowServiceInterface::class => LinkEngineModificationFromRowService::class,
    ];

    private const array CLIENT_BINDINGS = [
        TemplatesClientInterface::class => TemplatesClient::class,
    ];

    private const array FILE_BINDINGS = [
        ImportFileStorageInterface::class => LaravelImportFileStorage::class,
        LocalImportFileStorageInterface::class => LaravelLocalImportFileStorage::class,
    ];

    private const array PUBLISHER_BINDINGS = [
        LocalImportRequestPublisherInterface::class => RabbitMqLocalImportRequestPublisher::class,
    ];

    /**
     * Зарегистрировать container bindings фичи Import.
     *
     * Шаги:
     * 1) Зарегистрировать отдельные RabbitMQ/file report services.
     * 2) Последовательно привязать command, repository, import, factory и service ports.
     * 3) Зарегистрировать client/file/publisher adapters.
     */
    public function register(): void
    {
        // Уведомление о готовом файле уходит в RabbitMQ (сервису с Filament).
        $this->app->bind(FileNotificationServiceInterface::class, RabbitMqFileNotificationService::class);

        // Выгрузка отчёта об ошибках импорта (Excel → disk).
        $this->app->bind(ImportFailureReporterInterface::class, ImportFailureReporter::class);

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

        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::FILE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::PUBLISHER_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
