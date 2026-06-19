<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Application\Contracts\Commands\EngineModificationCommandInterface;
use App\Vehicles\Application\Contracts\Exports\ImportFailureReporterInterface;
use App\Vehicles\Application\Contracts\Notifications\FileNotificationServiceInterface;
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
                "App\\Vehicles\\Application\\Contracts\\Repositories\\{$entity}RepositoryInterface",
                "App\\Vehicles\\Infrastructure\\Repositories\\{$entity}Repository",
            );
            $this->app->bind(
                "App\\Vehicles\\Application\\Contracts\\Commands\\{$entity}CommandInterface",
                "App\\Vehicles\\Infrastructure\\Commands\\{$entity}Command",
            );
        }

        // EngineModification — только запись (пивот engine_modification), без репозитория.
        $this->app->bind(
            EngineModificationCommandInterface::class,
            EngineModificationCommand::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
