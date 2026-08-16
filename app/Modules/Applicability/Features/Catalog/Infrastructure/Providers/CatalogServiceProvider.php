<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Infrastructure\Providers;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Infrastructure\Repositories\CatalogApplicabilityRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует read-only зависимости Applicability Catalog.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    /** Регистрирует repository binding фичи. */
    public function register(): void
    {
        $this->app->bind(
            CatalogApplicabilityRepositoryInterface::class,
            CatalogApplicabilityRepository::class,
        );
    }
}
