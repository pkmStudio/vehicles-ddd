<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Infrastructure\Providers;

use App\Vehicles\Templates\Application\Factories\DetailsDataFactory;
use App\Vehicles\Templates\Application\Services\DetailsDataPresenter;
use App\Vehicles\Templates\Application\WiperSpecificationService;
use App\Vehicles\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Vehicles\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Vehicles\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги фичи Templates — используются и Import, и Export.
 */
final class TemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WiperSpecificationServiceInterface::class, WiperSpecificationService::class);
        $this->app->bind(DetailsDataFactoryInterface::class, DetailsDataFactory::class);
        $this->app->bind(DetailsDataPresenterInterface::class, DetailsDataPresenter::class);
    }
}
