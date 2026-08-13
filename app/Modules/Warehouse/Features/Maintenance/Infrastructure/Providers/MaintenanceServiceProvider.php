<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Maintenance\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Clients\KitPropertiesClient;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
    ];

    /**
     * Регистрирует client adapters Maintenance-фичи.
     * Шаги:
     * 1) Пройти список локальных client contracts и concrete adapters.
     * 2) Зарегистрировать каждую пару в Laravel container через bind().
     */
    public function register(): void
    {
        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
