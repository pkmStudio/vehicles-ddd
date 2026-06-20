<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Domain\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Infrastructure\Commands\EngineModificationCommand;
use App\Vehicles\Infrastructure\Exports\ImportFailureReporter;
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
        'App\\Vehicles\\Domain\\Contracts\\Imports\\ManufacturerCommandImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Manufacturer\\ManufacturerCommandImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\VehicleCommandImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Vehicle\\VehicleCommandImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EngineCommandImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Engine\\EngineCommandImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\ModificationCommandImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Modification\\ModificationCommandImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EngineModificationImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\EngineModification\\EngineModificationImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\VehicleMultiSheetImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Vehicle\\VehicleMultiSheetImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EngineMultiSheetImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Engine\\EngineMultiSheetImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EngineCrossImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Engine\\EngineCrossImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EngineSparkPlugSpecificationImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Engine\\EngineSparkPlugSpecificationImport',
        'App\\Vehicles\\Domain\\Contracts\\Imports\\EnginesCodeImportInterface' => 'App\\Vehicles\\Infrastructure\\Imports\\Engine\\EnginesCodeImport',
        'App\\Vehicles\\Domain\\Contracts\\Exports\\EngineMultiSheetExportInterface' => 'App\\Vehicles\\Infrastructure\\Exports\\Engine\\EngineMultiSheetExport',
        'App\\Vehicles\\Domain\\Contracts\\Exports\\VehicleMultiSheetExportInterface' => 'App\\Vehicles\\Infrastructure\\Exports\\Vehicle\\VehicleMultiSheetExport',
        'App\\Vehicles\\Domain\\Contracts\\Exports\\EngineKitApplicabilityExportInterface' => 'App\\Vehicles\\Infrastructure\\Exports\\Engine\\EngineKitApplicabilityExport',
        'App\\Vehicles\\Domain\\Contracts\\Exports\\VehicleKitApplicabilityExportInterface' => 'App\\Vehicles\\Infrastructure\\Exports\\Vehicle\\VehicleKitApplicabilityExport',
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
    }

    public function boot(): void
    {
        //
    }
}
