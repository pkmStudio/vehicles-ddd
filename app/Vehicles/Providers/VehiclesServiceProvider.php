<?php

declare(strict_types=1);

namespace App\Vehicles\Providers;

use App\Vehicles\Notifications\Contracts\FileNotificationServiceInterface;
use App\Vehicles\Notifications\RabbitMqFileNotificationService;
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

        // Repository (read) + Command (write) каждой сущности → их реализации.
        foreach (self::ENTITIES as $entity) {
            $this->app->bind(
                "App\\Vehicles\\Repositories\\{$entity}\\{$entity}RepositoryInterface",
                "App\\Vehicles\\Repositories\\{$entity}\\{$entity}Repository",
            );
            $this->app->bind(
                "App\\Vehicles\\Commands\\{$entity}\\{$entity}CommandInterface",
                "App\\Vehicles\\Commands\\{$entity}\\{$entity}Command",
            );
        }
    }

    public function boot(): void
    {
        //
    }
}
