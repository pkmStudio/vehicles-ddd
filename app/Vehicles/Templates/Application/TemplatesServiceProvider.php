<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application;

use App\Vehicles\Templates\Domain\Contracts\DetailTemplateResolverInterface;
use App\Vehicles\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Templates — используются и Import, и Export.
 */
final class TemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DetailTemplateResolverInterface::class, DetailTemplateResolver::class);
        $this->app->bind(WiperSpecificationServiceInterface::class, WiperSpecificationService::class);
    }
}
