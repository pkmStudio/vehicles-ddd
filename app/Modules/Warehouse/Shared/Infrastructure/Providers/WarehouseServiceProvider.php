<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Infrastructure\Providers;

use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use App\Modules\Warehouse\Shared\Infrastructure\Clients\WarehouseApplicabilityClient;
use Illuminate\Support\ServiceProvider;

/**
 * Домен Warehouse сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations (см. app/Modules/Vehicles/Shared/Infrastructure/Providers/VehiclesServiceProvider).
 */
final class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseApplicabilityClientInterface::class, WarehouseApplicabilityClient::class);
    }

    /**
     * Подключает миграции Warehouse из доменной инфраструктуры.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
