<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Catalog\Application\Clients\VehiclesApplicabilityClient;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\Contracts\Repositories\PartSpecificationDuplicateFinderInterface;
use App\Modules\Vehicles\Shared\Infrastructure\Repositories\PartSpecificationDuplicateFinder;
use Illuminate\Support\ServiceProvider;

/**
 * Домен Vehicles сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations.
 */
final class VehiclesServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует shared clients Vehicles для потребителей из других модулей.
     *
     * Шаги:
     * 1. Связать applicability-facing port с Catalog application client.
     * 2. Скрыть read-модель Catalog за shared contract.
     */
    public function register(): void
    {
        $this->app->bind(VehiclesApplicabilityClientInterface::class, VehiclesApplicabilityClient::class);
        $this->app->bind(PartSpecificationDuplicateFinderInterface::class, PartSpecificationDuplicateFinder::class);
    }

    /**
     * Подключает миграции схемы Vehicles из module-local директории.
     *
     * Шаги:
     * 1. Передать Laravel путь к shared migration files модуля Vehicles.
     * 2. Дать framework загрузить эти миграции вместе с остальной схемой приложения.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
