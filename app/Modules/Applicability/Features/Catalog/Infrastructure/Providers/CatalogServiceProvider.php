<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Infrastructure\Providers;

use App\Modules\Applicability\Features\Catalog\Application\UseCases\CheckNomenclatureApplicabilityUseCase;
use App\Modules\Applicability\Features\Catalog\Application\UseCases\ListApplicableCategoriesUseCase;
use App\Modules\Applicability\Features\Catalog\Application\UseCases\ListApplicableNomenclaturesUseCase;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\CheckNomenclatureApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableCategoriesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableNomenclaturesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Infrastructure\Repositories\CatalogApplicabilityRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует read-only зависимости Applicability Catalog.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    /** Регистрирует repository и use case bindings фичи. */
    public function register(): void
    {
        $this->app->bind(
            CatalogApplicabilityRepositoryInterface::class,
            CatalogApplicabilityRepository::class,
        );
        $this->app->bind(
            CheckNomenclatureApplicabilityUseCaseInterface::class,
            CheckNomenclatureApplicabilityUseCase::class,
        );
        $this->app->bind(
            ListApplicableCategoriesUseCaseInterface::class,
            ListApplicableCategoriesUseCase::class,
        );
        $this->app->bind(
            ListApplicableNomenclaturesUseCaseInterface::class,
            ListApplicableNomenclaturesUseCase::class,
        );
    }
}
