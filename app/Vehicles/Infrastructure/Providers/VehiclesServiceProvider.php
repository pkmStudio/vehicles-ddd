<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Application\Import\Factories\Engine\EngineDataFactory;
use App\Vehicles\Application\Import\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Application\Import\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Application\Import\Support\EngineMainSheetImportService;
use App\Vehicles\Application\Import\Support\DetailsBuilder;
use App\Vehicles\Application\Import\Support\TemplateDataBuilder;
use App\Vehicles\Application\Import\UseCases\Engine\AssignEngineGroupUseCase;
use App\Vehicles\Application\Import\UseCases\Engine\UpsertEngineFromSheetUseCase;
use App\Vehicles\Application\Import\UseCases\Engine\UpsertEngineSparkPlugSpecUseCase;
use App\Vehicles\Application\Import\UseCases\Engine\UpdateEngineEditableFieldsUseCase;
use App\Vehicles\Application\Import\UseCases\Engine\UpsertSparkPlugSpecByModificationUseCase;
use App\Vehicles\Application\Import\UseCases\EngineModification\LinkEngineModificationFromRowUseCase;
use App\Vehicles\Application\Import\UseCases\Manufacturer\UpsertManufacturerFromRowUseCase;
use App\Vehicles\Application\Import\UseCases\Modification\UpsertModificationFromRowUseCase;
use App\Vehicles\Application\Import\UseCases\Reporting\ReportImportResultUseCase;
use App\Vehicles\Application\Import\UseCases\Vehicle\UpsertVehicleFromSheetUseCase;
use App\Vehicles\Application\Import\UseCases\Vehicle\UpsertVehicleFromTdRowUseCase;
use App\Vehicles\Application\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCase;
use App\Vehicles\Application\Import\Services\EngineModificationReadinessGate;
use App\Vehicles\Application\Export\Services\EngineExportService;
use App\Vehicles\Application\Export\Services\VehicleExportService;
use App\Vehicles\Application\Export\Support\ExportDetailsBuilder;
use App\Vehicles\Application\Export\Support\PartSpecificationRowExpander;
use App\Vehicles\Application\Export\Support\VehicleExportRow;
use App\Vehicles\Application\Export\Support\EngineExportRow;
use App\Vehicles\Application\Export\Support\WiperRowExpander;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\DetailsBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\EngineMainSheetImportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\TemplateDataBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\AssignEngineGroupUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpsertEngineFromSheetUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpsertEngineSparkPlugSpecUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpsertSparkPlugSpecByModificationUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpdateEngineEditableFieldsUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\EngineModification\LinkEngineModificationFromRowUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Manufacturer\UpsertManufacturerFromRowUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Modification\UpsertModificationFromRowUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Reporting\ReportImportResultUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleFromSheetUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleFromTdRowUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleWiperSpecUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Services\EngineModificationReadinessGateInterface;
use App\Vehicles\Domain\Contracts\Application\Common\Services\DetailTemplateResolverInterface;
use App\Vehicles\Domain\Contracts\Application\Common\Services\WiperSpecificationServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\EngineExportRowInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\ExportDetailsBuilderInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\PartSpecificationRowExpanderInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\VehicleExportRowInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\EngineExportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Services\VehicleExportServiceInterface;
use App\Vehicles\Domain\Contracts\Application\Export\Support\WiperRowExpanderInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\FailuresExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\ImportFailureReporterInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\VehicleMultiSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\EngineMainSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\EngineSparkPlugsSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\VehicleMainSheetExportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\Sheets\VehicleWipersSheetExportInterface;
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
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\EngineMainSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\EngineSparkPlugsSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\VehicleMainSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\VehicleWipersSheetImportInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Messaging\RabbitMQPublisherInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Application\Common\DetailTemplateResolver;
use App\Vehicles\Domain\Services\WiperSpecificationService;
use App\Vehicles\Infrastructure\Commands\EngineModificationCommand;
use App\Vehicles\Infrastructure\Exports\Engine\EngineMultiSheetExport;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use App\Vehicles\Infrastructure\Exports\ImportFailureReporter;
use App\Vehicles\Infrastructure\Exports\FailuresExport;
use App\Vehicles\Infrastructure\Exports\Vehicle\VehicleMultiSheetExport;
use App\Vehicles\Infrastructure\Exports\Vehicle\Sheets\VehicleMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Vehicle\Sheets\VehicleWipersSheetExport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCommandImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineCrossImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineMultiSheetImport;
use App\Vehicles\Infrastructure\Imports\Engine\EnginesCodeImport;
use App\Vehicles\Infrastructure\Imports\Engine\EngineSparkPlugSpecificationImport;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Engine\Sheets\EngineSparkPlugsSheetImport;
use App\Vehicles\Infrastructure\Imports\EngineModification\EngineModificationImport;
use App\Vehicles\Infrastructure\Imports\Manufacturer\ManufacturerCommandImport;
use App\Vehicles\Infrastructure\Imports\Modification\ModificationCommandImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\VehicleCommandImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\VehicleMultiSheetImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleMainSheetImport;
use App\Vehicles\Infrastructure\Imports\Vehicle\Sheets\VehicleWipersSheetImport;
use App\Vehicles\Infrastructure\Messaging\RabbitMQPublisher;
use App\Vehicles\Infrastructure\Notifications\RabbitMqFileNotificationService;
use Illuminate\Support\ServiceProvider;

/**
 * Корневой сервис-провайдер домена Vehicles.
 * Здесь — контейнерные биндинги домена (интерфейс → реализация).
 * Связи событий и слушателей живут отдельно, в EventServiceProvider.
 */
class VehiclesServiceProvider extends ServiceProvider
{
    /**
     * Сущности домена с парой Repository (чтение) + Command (запись).
     * Добавить сущность = одна строка.
     */
    private const ENTITIES = [
        'Vehicle',
        'Modification',
        'Engine',
        'PartSpecification',
        'Manufacturer',
        'Feature',
        'FeatureValue',
    ];

    /**
     * Порты импорта/экспорта (Domain\Contracts) → реализации (Infrastructure\Imports / Infrastructure\Exports).
     * Поведенческие порты: import(path) / parse(path) / download(fileName); Excel — внутри реализации.
     */
    private const IMPORT_EXPORT_BINDINGS = [
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
     * Порты Excel-листов (Infrastructure/Sheets) → реализации (Infrastructure/Sheets).
     */
    private const IMPORT_EXPORT_SHEET_BINDINGS = [
        EngineMainSheetImportInterface::class => EngineMainSheetImport::class,
        EngineSparkPlugsSheetImportInterface::class => EngineSparkPlugsSheetImport::class,
        VehicleMainSheetImportInterface::class => VehicleMainSheetImport::class,
        VehicleWipersSheetImportInterface::class => VehicleWipersSheetImport::class,
        EngineMainSheetExportInterface::class => EngineMainSheetExport::class,
        EngineSparkPlugsSheetExportInterface::class => EngineSparkPlugsSheetExport::class,
        VehicleMainSheetExportInterface::class => VehicleMainSheetExport::class,
        VehicleWipersSheetExportInterface::class => VehicleWipersSheetExport::class,
        FailuresExportInterface::class => FailuresExport::class,
    ];

    /**
     * Порты фабрик (Domain\Contracts\Import\Factories) → реализации (Application\Import\Factories).
     */
    private const FACTORY_BINDINGS = [
        EngineDataFactoryInterface::class => EngineDataFactory::class,
        VehicleDataFactoryInterface::class => VehicleDataFactory::class,
        ModificationDataFactoryInterface::class => ModificationDataFactory::class,
        ManufacturerDataFactoryInterface::class => ManufacturerDataFactory::class,
        EngineModificationDataFactoryInterface::class => EngineModificationDataFactory::class,
    ];

    /**
     * Порты сервисов/поддержки application-слоя → concrete реализации.
     */
    private const SUPPORT_BINDINGS = [
        DetailTemplateResolverInterface::class => DetailTemplateResolver::class,
        WiperSpecificationServiceInterface::class => WiperSpecificationService::class,
        ExportDetailsBuilderInterface::class => ExportDetailsBuilder::class,
        PartSpecificationRowExpanderInterface::class => PartSpecificationRowExpander::class,
        VehicleExportRowInterface::class => VehicleExportRow::class,
        EngineExportRowInterface::class => EngineExportRow::class,
        WiperRowExpanderInterface::class => WiperRowExpander::class,
        TemplateDataBuilderInterface::class => TemplateDataBuilder::class,
        DetailsBuilderInterface::class => DetailsBuilder::class,
        EngineMainSheetImportServiceInterface::class => EngineMainSheetImportService::class,
        EngineExportServiceInterface::class => EngineExportService::class,
        VehicleExportServiceInterface::class => VehicleExportService::class,
        EngineModificationReadinessGateInterface::class => EngineModificationReadinessGate::class,
        ReportImportResultUseCaseInterface::class => ReportImportResultUseCase::class,
    ];

    /**
     * Порты use-case сценариев → реализации (Application\Import\UseCases).
     */
    private const USECASE_BINDINGS = [
        UpsertEngineFromSheetUseCaseInterface::class => UpsertEngineFromSheetUseCase::class,
        UpsertEngineSparkPlugSpecUseCaseInterface::class => UpsertEngineSparkPlugSpecUseCase::class,
        UpsertSparkPlugSpecByModificationUseCaseInterface::class => UpsertSparkPlugSpecByModificationUseCase::class,
        AssignEngineGroupUseCaseInterface::class => AssignEngineGroupUseCase::class,
        UpdateEngineEditableFieldsUseCaseInterface::class => UpdateEngineEditableFieldsUseCase::class,
        UpsertManufacturerFromRowUseCaseInterface::class => UpsertManufacturerFromRowUseCase::class,
        UpsertModificationFromRowUseCaseInterface::class => UpsertModificationFromRowUseCase::class,
        UpsertVehicleFromSheetUseCaseInterface::class => UpsertVehicleFromSheetUseCase::class,
        UpsertVehicleFromTdRowUseCaseInterface::class => UpsertVehicleFromTdRowUseCase::class,
        UpsertVehicleWiperSpecUseCaseInterface::class => UpsertVehicleWiperSpecUseCase::class,
        LinkEngineModificationFromRowUseCaseInterface::class => LinkEngineModificationFromRowUseCase::class,
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
        foreach (self::ENTITIES as $entity) {
            $this->app->bind(
                "App\\Vehicles\\Domain\\Contracts\\Repositories\\{$entity}RepositoryInterface",
                "App\\Vehicles\\Infrastructure\\Repositories\\{$entity}Repository",
            );
            $this->app->bind(
                "App\\Vehicles\\Domain\\Contracts\\Commands\\{$entity}CommandInterface",
                "App\\Vehicles\\Infrastructure\\Commands\\{$entity}Command",
            );
        }

        // EngineModification — только запись (пивот engine_modification), без репозитория.
        $this->app->bind(
            EngineModificationCommandInterface::class,
            EngineModificationCommand::class,
        );

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

        // Порты application-слоя (сервисы/поддержка/сценарии) → реализации.
        foreach (self::SUPPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::USECASE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        // Итого для внутреннего DI.
        $this->app->bind(
            RabbitMQPublisherInterface::class,
            RabbitMQPublisher::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
