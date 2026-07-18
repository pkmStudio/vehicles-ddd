<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Clients\TemplatesClient;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TemplatesClientInterface::class, TemplatesClient::class);
    }
}
