<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Application\Common\Services\DetailTemplateResolver;
use App\Vehicles\Application\Common\Services\WiperSpecificationService;
use App\Vehicles\Application\Export\Services\EngineExportService;
use App\Vehicles\Application\Export\Services\VehicleExportService;
use App\Vehicles\Application\Export\Services\Rows\EngineExportRow;
use App\Vehicles\Application\Export\Services\Details\ExportDetailsBuilder;
use App\Vehicles\Application\Export\Services\Expanders\PartSpecificationRowExpander;
use App\Vehicles\Application\Export\Services\Rows\VehicleExportRow;
use App\Vehicles\Application\Export\Services\Expanders\WiperRowExpander;
use App\Vehicles\Application\Import\Factories\Engine\EngineDataFactory;
use App\Vehicles\Application\Import\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Application\Import\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Application\Import\Services\EngineImportService;
use App\Vehicles\Application\Import\Services\Engine\AssignEngineGroupService;
use App\Vehicles\Application\Import\Services\Engine\EngineEditableColumnsMapper;
use App\Vehicles\Application\Import\Services\Engine\UpdateEngineEditableFieldsService;
use App\Vehicles\Application\Import\Services\Engine\UpsertEngineFromSheetService;
use App\Vehicles\Application\Import\Services\Engine\UpsertEngineSparkPlugSpecService;
use App\Vehicles\Application\Import\Services\Engine\UpsertSparkPlugSpecByModificationService;
use App\Vehicles\Application\Import\Services\EngineModification\LinkEngineModificationFromRowService;
use App\Vehicles\Application\Import\Services\EngineModificationReadinessGate;
use App\Vehicles\Application\Import\Services\Manufacturer\UpsertManufacturerFromRowService;
use App\Vehicles\Application\Import\Services\Modification\UpsertModificationFromRowService;
use App\Vehicles\Application\Import\Services\Reporting\ReportImportResultService;
use App\Vehicles\Application\Import\Services\Template\DetailsBuilder;
use App\Vehicles\Application\Import\Services\Template\TemplateDataBuilder;
use App\Vehicles\Application\Import\Services\Vehicle\UpsertVehicleFromSheetService;
use App\Vehicles\Application\Import\Services\Vehicle\UpsertVehicleFromTdRowService;
use App\Vehicles\Application\Import\Services\Vehicle\VehicleWiperSpecificationImportService;
use App\Vehicles\Application\Import\Services\VehicleImportService;
use App\Vehicles\Domain\Contracts\Application\Common\Services\DetailTemplateResolverInterface;
use App\Vehicles\Domain\Contracts\Application\Common\Services\WiperSpecificationServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\EngineExportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\VehicleExportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\Rows\EngineExportRowInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\Details\ExportDetailsBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\Expanders\PartSpecificationRowExpanderInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\Rows\VehicleExportRowInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\Expanders\WiperRowExpanderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineImportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModificationReadinessGateInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\VehicleImportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Template\DetailsBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\EngineEditableColumnsMapperInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\AssignEngineGroupServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpdateEngineEditableFieldsServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Engine\UpsertSparkPlugSpecByModificationServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModification\LinkEngineModificationFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Manufacturer\UpsertManufacturerFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Modification\UpsertModificationFromRowServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Reporting\ReportImportResultServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle\UpsertVehicleFromTdRowServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\FeatureCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\FeatureValueCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ManufacturerCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\ModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\VehicleCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\FailuresExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\ImportFailureReporterInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineCommandImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineCrossImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineModificationImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineMultiSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EnginesCodeImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\EngineSparkPlugSpecificationImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\ManufacturerCommandImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\ModificationCommandImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\VehicleCommandImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\VehicleMultiSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Infrastructure\Commands\EngineCommand;
use App\Vehicles\Infrastructure\Commands\EngineModificationCommand;
use App\Vehicles\Infrastructure\Commands\FeatureCommand;
use App\Vehicles\Infrastructure\Commands\FeatureValueCommand;
use App\Vehicles\Infrastructure\Commands\ManufacturerCommand;
use App\Vehicles\Infrastructure\Commands\ModificationCommand;
use App\Vehicles\Infrastructure\Commands\PartSpecificationCommand;
use App\Vehicles\Infrastructure\Commands\VehicleCommand;
use App\Vehicles\Infrastructure\Exports\Engine\EngineMultiSheetExport;
use App\Vehicles\Infrastructure\Exports\FailuresExport;
use App\Vehicles\Infrastructure\Exports\ImportFailureReporter;
use App\Vehicles\Infrastructure\Exports\Vehicle\VehicleMultiSheetExport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCommandImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCrossImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineMultiSheetImport;
use App\Vehicles\Infrastructure\Imports\Engine\EnginesCodeImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineSparkPlugSpecificationImport;
use App\Vehicles\Infrastructure\Imports\EngineModification\EngineModificationImport;
use App\Vehicles\Infrastructure\Imports\Manufacturer\ManufacturerCommandImport;
use App\Vehicles\Infrastructure\Imports\Modification\ModificationCommandImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\VehicleCommandImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\VehicleMultiSheetImport;
use App\Vehicles\Infrastructure\Notifications\RabbitMqFileNotificationService;
use App\Vehicles\Infrastructure\Repositories\EngineRepository;
use App\Vehicles\Infrastructure\Repositories\FeatureRepository;
use App\Vehicles\Infrastructure\Repositories\FeatureValueRepository;
use App\Vehicles\Infrastructure\Repositories\ManufacturerRepository;
use App\Vehicles\Infrastructure\Repositories\ModificationRepository;
use App\Vehicles\Infrastructure\Repositories\PartSpecificationRepository;
use App\Vehicles\Infrastructure\Repositories\VehicleRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Корневой сервис-провайдер домена Vehicles.
 * Здесь — контейнерные биндинги домена (интерфейс → реализация).
 * Связи событий и слушателей живут отдельно, в EventServiceProvider.
 */
class VehiclesServiceProvider extends ServiceProvider
{
    /**
     * Порты Репозиториев и Команд
     */
    private const array REPOSITORY_BINDINGS = [
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        VehicleRepositoryInterface::class => VehicleRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
        FeatureRepositoryInterface::class => FeatureRepository::class,
        FeatureValueRepositoryInterface::class => FeatureValueRepository::class,
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

    /**
     * Порты импорта/экспорта (Domain\Contracts) → реализации (Infrastructure\Imports / Infrastructure\Exports).
     * Поведенческие порты: import(path) / parse(path) / download(fileName); Excel — внутри реализации.
     */
    private const array IMPORT_EXPORT_BINDINGS = [
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
        EngineMultiSheetExportInterface::class => EngineMultiSheetExport::class,
        VehicleMultiSheetExportInterface::class => VehicleMultiSheetExport::class,
    ];

    /**
     * Порты Excel-листов / сервисы экспорта, которые остаются через порты.
     */
    private const array IMPORT_EXPORT_SHEET_BINDINGS = [
        FailuresExportInterface::class => FailuresExport::class,
    ];

    /**
     * Порты фабрик (Domain\Contracts\Import\Factories) → реализации (Application\Import\Factories).
     */
    private const array FACTORY_BINDINGS = [
        EngineDataFactoryInterface::class => EngineDataFactory::class,
        VehicleDataFactoryInterface::class => VehicleDataFactory::class,
        ModificationDataFactoryInterface::class => ModificationDataFactory::class,
        ManufacturerDataFactoryInterface::class => ManufacturerDataFactory::class,
        EngineModificationDataFactoryInterface::class => EngineModificationDataFactory::class,
    ];

    /**
     * Порты сервисов application-слоя → concrete реализации.
     */
    private const array SERVICE_BINDINGS = [
        DetailTemplateResolverInterface::class => DetailTemplateResolver::class,
        WiperSpecificationServiceInterface::class => WiperSpecificationService::class,
        ExportDetailsBuilderInterface::class => ExportDetailsBuilder::class,
        PartSpecificationRowExpanderInterface::class => PartSpecificationRowExpander::class,
        VehicleExportRowInterface::class => VehicleExportRow::class,
        EngineExportRowInterface::class => EngineExportRow::class,
        WiperRowExpanderInterface::class => WiperRowExpander::class,
        TemplateDataBuilderInterface::class => TemplateDataBuilder::class,
        DetailsBuilderInterface::class => DetailsBuilder::class,
        EngineEditableColumnsMapperInterface::class => EngineEditableColumnsMapper::class,
        EngineImportServiceInterface::class => EngineImportService::class,
        VehicleImportServiceInterface::class => VehicleImportService::class,
        EngineExportServiceInterface::class => EngineExportService::class,
        VehicleExportServiceInterface::class => VehicleExportService::class,
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
        $this->app->bind(
            FileNotificationServiceInterface::class,
            RabbitMqFileNotificationService::class,
        );

        // Выгрузка отчёта об ошибках импорта (Excel → S3).
        $this->app->bind(
            ImportFailureReporterInterface::class,
            ImportFailureReporter::class,
        );

        // Repository (read) + Command (write) каждой сущности → их реализации.
        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Порты импорта/экспорта → реализации.
        foreach (self::IMPORT_EXPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::IMPORT_EXPORT_SHEET_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Порты фабрик сборки ModelData → реализации (Application/Import/Factories).
        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Порты application-слоя → реализации.
        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}
