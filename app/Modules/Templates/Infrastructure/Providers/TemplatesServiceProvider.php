<?php

declare(strict_types=1);

namespace App\Modules\Templates\Infrastructure\Providers;

use App\Modules\Templates\Application\Clients\TemplatesClient;
use App\Modules\Templates\Application\Factories\DetailsDataFactory;
use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Application\Services\DetailsDataPresenter;
use App\Modules\Templates\Application\Services\NomenclatureDetailsDataPresenter;
use App\Modules\Templates\Application\WiperSpecificationService;
use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\Services\NomenclatureDetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Биндинги shared kernel Templates — используются доменами Vehicles и Warehouse.
 */
final class TemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WiperSpecificationServiceInterface::class, WiperSpecificationService::class);
        $this->app->bind(DetailsDataFactoryInterface::class, DetailsDataFactory::class);
        $this->app->bind(DetailsDataPresenterInterface::class, DetailsDataPresenter::class);
        $this->app->bind(NomenclatureDetailsDataFactoryInterface::class, NomenclatureDetailsDataFactory::class);
        $this->app->bind(NomenclatureDetailsDataPresenterInterface::class, NomenclatureDetailsDataPresenter::class);
        $this->app->bind(TemplatesClientInterface::class, TemplatesClient::class);
    }
}
