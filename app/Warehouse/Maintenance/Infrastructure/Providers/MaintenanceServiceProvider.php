<?php

declare(strict_types=1);

namespace App\Warehouse\Maintenance\Infrastructure\Providers;

use App\Warehouse\Maintenance\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Warehouse\Maintenance\Infrastructure\Clients\KitPropertiesClient;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
    ];

    public function register(): void
    {
        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
