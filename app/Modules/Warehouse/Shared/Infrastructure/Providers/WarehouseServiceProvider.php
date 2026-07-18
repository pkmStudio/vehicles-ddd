<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Домен Warehouse сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations (см. app/Modules/Vehicles/Shared/Infrastructure/Providers/VehiclesServiceProvider).
 */
final class WarehouseServiceProvider extends ServiceProvider
{
    /**
     * Подключает миграции Warehouse из доменной инфраструктуры.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
