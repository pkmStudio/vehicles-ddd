<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM\BrandCrmClient;
use App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM\KitCrmClient;
use App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM\NomenclatureCrmClient;
use App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM\PackDimensionCrmClient;
use App\Modules\Warehouse\Features\Catalog\Application\Services\WarehouseCatalogCascadeDeleteService;
use App\Modules\Warehouse\Features\Catalog\Application\Services\WarehouseCatalogMutationResultService;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\BrandCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\NomenclatureCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\PackDimensionCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Clients\KitPropertiesClient;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands\BrandCommand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands\KitCommand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands\NomenclatureCommand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands\PackDimensionCommand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Notifications\RabbitMqWarehouseCatalogMutationNotificationService;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\Applicability\WarehouseApplicabilityRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\BrandCrmRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\BrandRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\KitCrmRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\KitRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\NomenclatureCrmRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\PackDimensionCrmRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\PackDimensionRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\TypeRepository;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Services\WarehouseCatalogMutationCacheService;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги фичи Warehouse Catalog.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    private const array COMMAND_BINDINGS = [
        BrandCommandInterface::class => BrandCommand::class,
        NomenclatureCommandInterface::class => NomenclatureCommand::class,
        PackDimensionCommandInterface::class => PackDimensionCommand::class,
        KitCommandInterface::class => KitCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        BrandRepositoryInterface::class => BrandRepository::class,
        BrandCrmRepositoryInterface::class => BrandCrmRepository::class,
        TypeRepositoryInterface::class => TypeRepository::class,
        NomenclatureRepositoryInterface::class => NomenclatureRepository::class,
        NomenclatureCrmRepositoryInterface::class => NomenclatureCrmRepository::class,
        KitCrmRepositoryInterface::class => KitCrmRepository::class,
        PackDimensionCrmRepositoryInterface::class => PackDimensionCrmRepository::class,
        PackDimensionRepositoryInterface::class => PackDimensionRepository::class,
        KitRepositoryInterface::class => KitRepository::class,
        WarehouseApplicabilityRepositoryInterface::class => WarehouseApplicabilityRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        WarehouseCatalogCascadeDeleteServiceInterface::class => WarehouseCatalogCascadeDeleteService::class,
        WarehouseCatalogMutationCacheServiceInterface::class => WarehouseCatalogMutationCacheService::class,
        WarehouseCatalogMutationNotificationServiceInterface::class => RabbitMqWarehouseCatalogMutationNotificationService::class,
        WarehouseCatalogMutationResultServiceInterface::class => WarehouseCatalogMutationResultService::class,
    ];

    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
        BrandCrmClientInterface::class => BrandCrmClient::class,
        NomenclatureCrmClientInterface::class => NomenclatureCrmClient::class,
        KitCrmClientInterface::class => KitCrmClient::class,
        PackDimensionCrmClientInterface::class => PackDimensionCrmClient::class,
    ];

    /**
     * Регистрирует все биндинги фичи Warehouse Catalog в контейнере.
     *
     * Шаги:
     * 1) Зарегистрировать команды записи CRUD-сценариев.
     * 2) Зарегистрировать repositories, services и outbound notification adapter.
     */
    public function register(): void
    {
        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
