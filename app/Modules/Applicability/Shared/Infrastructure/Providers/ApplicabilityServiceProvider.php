<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Infrastructure\Providers;

use App\Modules\Applicability\Features\Calculation\Infrastructure\Providers\CalculationServiceProvider;
use App\Modules\Applicability\Features\Export\Infrastructure\Providers\ExportServiceProvider;
use App\Modules\Applicability\Features\Import\Infrastructure\Providers\ImportEventServiceProvider;
use App\Modules\Applicability\Features\Import\Infrastructure\Providers\ImportServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Подключает module-level инфраструктуру Applicability и провайдеры фич.
 */
final class ApplicabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(CalculationServiceProvider::class);
        $this->app->register(ImportServiceProvider::class);
        $this->app->register(ImportEventServiceProvider::class);
        $this->app->register(ExportServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
