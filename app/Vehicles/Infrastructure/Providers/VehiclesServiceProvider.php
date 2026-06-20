<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Application\Import\Factories\Engine\EngineDataFactory;
use App\Vehicles\Application\Import\Factories\EngineModification\EngineModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Manufacturer\ManufacturerDataFactory;
use App\Vehicles\Application\Import\Factories\Modification\ModificationDataFactory;
use App\Vehicles\Application\Import\Factories\Vehicle\VehicleDataFactory;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\EngineModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ManufacturerDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\ModificationDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Factories\VehicleDataFactoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\EngineMultiSheetExportInterface;
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
use App\Vehicles\Infrastructure\Commands\EngineModificationCommand;
use App\Vehicles\Infrastructure\Exports\Engine\EngineMultiSheetExport;
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
     * Порты фабрик (Domain\Contracts\Import\Factories) → реализации (Application\Import\Factories).
     */
    private const FACTORY_BINDINGS = [
        EngineDataFactoryInterface::class => EngineDataFactory::class,
        VehicleDataFactoryInterface::class => VehicleDataFactory::class,
        ModificationDataFactoryInterface::class => ModificationDataFactory::class,
        ManufacturerDataFactoryInterface::class => ManufacturerDataFactory::class,
        EngineModificationDataFactoryInterface::class => EngineModificationDataFactory::class,
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

        // Порты фабрик сборки ModelData → реализации (Application/Import/Factories).
        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}
