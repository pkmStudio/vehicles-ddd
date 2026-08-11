<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Catalog\Application\Clients\VehiclesApplicabilityClient;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Домен Vehicles сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations.
 */
final class VehiclesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VehiclesApplicabilityClientInterface::class, VehiclesApplicabilityClient::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
