<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM\EngineCrmClient;
use App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM\ManufacturerCrmClient;
use App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM\VehicleCrmClient;
use App\Modules\Vehicles\Features\Catalog\Application\Clients\VehicleCatalogClient;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\PartSpecificationMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\PartSpecificationOwnerResolverFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Services\CatalogCascadeDeleteService;
use App\Modules\Vehicles\Features\Catalog\Application\Services\CatalogMutationResultService;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\EnginePartSpecificationOwnerResolver;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\PartSpecificationDetailsWritePolicy;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\VehiclePartSpecificationOwnerResolver;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\EngineCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\ManufacturerCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCatalogClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications\EngineBulkDeleteNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications\VehicleBulkDeleteNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehiclesApplicabilityRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationDetailsWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Cache\LaravelCatalogMutationCacheService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ManufacturerCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\PartSpecificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\VehicleCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications\RabbitMqCatalogMutationNotificationService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications\RabbitMqEngineBulkDeleteNotificationService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications\RabbitMqVehicleBulkDeleteNotificationService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\EngineCrmRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\EngineRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\ManufacturerCrmRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\ManufacturerRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\ModificationRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\PartSpecificationRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\VehicleCrmRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehiclesApplicabilityRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги фичи Catalog в контейнере Laravel.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    private const array FACTORY_BINDINGS = [
        PartSpecificationMutationRequestFactoryInterface::class => PartSpecificationMutationRequestFactory::class,
        PartSpecificationOwnerResolverFactoryInterface::class => PartSpecificationOwnerResolverFactory::class,
    ];

    private const array CLIENT_BINDINGS = [
        VehicleCatalogClientInterface::class => VehicleCatalogClient::class,
        VehicleCrmClientInterface::class => VehicleCrmClient::class,
        EngineCrmClientInterface::class => EngineCrmClient::class,
        ManufacturerCrmClientInterface::class => ManufacturerCrmClient::class,
    ];

    private const array COMMAND_BINDINGS = [
        VehicleCommandInterface::class => VehicleCommand::class,
        ManufacturerCommandInterface::class => ManufacturerCommand::class,
        EngineCommandInterface::class => EngineCommand::class,
        EngineModificationCommandInterface::class => EngineModificationCommand::class,
        ModificationCommandInterface::class => ModificationCommand::class,
        PartSpecificationCommandInterface::class => PartSpecificationCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        VehicleRepositoryInterface::class => VehicleRepository::class,
        VehicleCrmRepositoryInterface::class => VehicleCrmRepository::class,
        EngineCrmRepositoryInterface::class => EngineCrmRepository::class,
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        ManufacturerCrmRepositoryInterface::class => ManufacturerCrmRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
        VehiclesApplicabilityRepositoryInterface::class => VehiclesApplicabilityRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        CatalogCascadeDeleteServiceInterface::class => CatalogCascadeDeleteService::class,
        CatalogMutationCacheServiceInterface::class => LaravelCatalogMutationCacheService::class,
        CatalogMutationNotificationServiceInterface::class => RabbitMqCatalogMutationNotificationService::class,
        VehicleBulkDeleteNotificationServiceInterface::class => RabbitMqVehicleBulkDeleteNotificationService::class,
        EngineBulkDeleteNotificationServiceInterface::class => RabbitMqEngineBulkDeleteNotificationService::class,
        CatalogMutationResultServiceInterface::class => CatalogMutationResultService::class,
        VehiclePartSpecificationOwnerResolverInterface::class => VehiclePartSpecificationOwnerResolver::class,
        EnginePartSpecificationOwnerResolverInterface::class => EnginePartSpecificationOwnerResolver::class,
        PartSpecificationDetailsWritePolicyInterface::class => PartSpecificationDetailsWritePolicy::class,
    ];

    /**
     * Регистрирует все биндинги фичи Catalog в контейнере.
     *
     * Шаги:
     * 1) Зарегистрировать use case биндинги.
     * 2) Зарегистрировать command/repository/factory биндинги.
     * 3) Зарегистрировать service/notification биндинги.
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

        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
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

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
