<?php

declare(strict_types=1);

namespace App\Templates\Infrastructure\Providers;

use App\Templates\Application\Factories\DetailsDataFactory;
use App\Templates\Application\Services\DetailsDataPresenter;
use App\Templates\Application\WiperSpecificationService;
use App\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
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
