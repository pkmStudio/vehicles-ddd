<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Домен Vehicles сам владеет своей схемой — миграции лежат рядом с доменом, а не в общем
 * database/migrations.
 */
final class VehiclesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
