<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Clients\TemplatesClient;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует adapters, нужные maintenance-сценариям Vehicles.
     *
     * Шаги:
     * 1. Связать feature-local Templates client port с infrastructure adapter.
     * 2. Оставить бизнес-сервисам зависимость только от Maintenance contract.
     */
    public function register(): void
    {
        $this->app->bind(TemplatesClientInterface::class, TemplatesClient::class);
    }
}
