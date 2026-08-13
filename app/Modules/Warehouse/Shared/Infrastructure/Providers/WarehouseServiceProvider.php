<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Catalog\Application\Clients\WarehouseApplicabilityClient;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Домен Warehouse сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations (см. app/Modules/Vehicles/Shared/Infrastructure/Providers/VehiclesServiceProvider).
 */
final class WarehouseServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует публичные client contracts Warehouse module-level Shared.
     * Шаги:
     * 1) Связать WarehouseApplicabilityClientInterface с Catalog adapter-ом владельца данных.
     */
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
