<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Infrastructure\Providers;

use App\Modules\Applicability\Features\Calculation\Infrastructure\Providers\CalculationEventServiceProvider;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Providers\CalculationServiceProvider;
use App\Modules\Applicability\Features\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Applicability\Features\Export\Infrastructure\Providers\ExportServiceProvider;
use App\Modules\Applicability\Features\Import\Infrastructure\Providers\ImportEventServiceProvider;
use App\Modules\Applicability\Features\Import\Infrastructure\Providers\ImportServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Подключает module-level инфраструктуру Applicability и провайдеры фич.
 */
final class ApplicabilityServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует provider-ы фич Applicability.
     *
     * Шаги:
     * 1. Подключает calculation service и event providers.
     * 2. Подключает import service и event providers.
     * 3. Подключает export service provider.
     */
    public function register(): void
    {
        $this->app->register(CalculationServiceProvider::class);
        $this->app->register(CalculationEventServiceProvider::class);
        $this->app->register(CatalogServiceProvider::class);
        $this->app->register(ImportServiceProvider::class);
        $this->app->register(ImportEventServiceProvider::class);
        $this->app->register(ExportServiceProvider::class);
    }

    /**
     * Подключает module-level migrations Applicability.
     *
     * Шаги:
     * 1. Указывает Laravel путь к shared migrations модуля.
     * 2. Позволяет миграциям Applicability участвовать в общем migration flow.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
