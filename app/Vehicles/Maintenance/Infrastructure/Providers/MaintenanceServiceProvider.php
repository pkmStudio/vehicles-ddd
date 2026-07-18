<?php

declare(strict_types=1);

namespace App\Vehicles\Maintenance\Infrastructure\Providers;

use App\Vehicles\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Vehicles\Maintenance\Infrastructure\Clients\TemplatesClient;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TemplatesClientInterface::class, TemplatesClient::class);
    }
}
