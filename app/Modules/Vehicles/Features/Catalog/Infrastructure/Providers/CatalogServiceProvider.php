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
use App\Modules\Vehicles\Features\Catalog\Application\Services\Vehicle\VehicleMutationWritePolicy;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Modification\ShowModificationForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine\ListEnginesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Engine\ShowEngineForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Manufacturer\ListManufacturersForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Manufacturer\ShowManufacturerForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle\ListVehicleCrmRelationsUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\CreateEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\DeleteEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\StartEngineMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Engine\UpdateEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer\CreateManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer\DeleteManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer\StartManufacturerMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Manufacturer\UpdateManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification\CreateModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification\DeleteModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification\StartModificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Modification\UpdateModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification\CreatePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification\DeletePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification\StartPartSpecificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\PartSpecification\UpdatePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle\CreateVehicleUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle\DeleteVehicleUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle\StartVehicleMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Mutations\Vehicle\UpdateVehicleUseCase;
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
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\Vehicle\VehicleMutationWritePolicyInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Engine\ListEnginesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Engine\ShowEngineForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer\ListManufacturersForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer\ShowManufacturerForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmOptionsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmRelationsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\CreateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\DeleteEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\StartEngineMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Engine\UpdateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\CreateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\DeleteModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\StartModificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Modification\UpdateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\StartPartSpecificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\PartSpecification\UpdatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\CreateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\DeleteVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Mutations\Vehicle\UpdateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Cache\LaravelCatalogMutationCacheService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ManufacturerCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\PartSpecificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\VehicleCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications\RabbitMqCatalogMutationNotificationService;
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
    private const array USE_CASE_BINDINGS = [
        StartVehicleMutationUseCaseInterface::class => StartVehicleMutationUseCase::class,
        CreateVehicleUseCaseInterface::class => CreateVehicleUseCase::class,
        UpdateVehicleUseCaseInterface::class => UpdateVehicleUseCase::class,
        DeleteVehicleUseCaseInterface::class => DeleteVehicleUseCase::class,
        ListManufacturerVehiclesForCatalogUseCaseInterface::class => ListManufacturerVehiclesForCatalogUseCase::class,
        ListVehiclesForCrmUseCaseInterface::class => ListVehiclesForCrmUseCase::class,
        ListVehicleCrmOptionsUseCaseInterface::class => ListVehicleCrmOptionsUseCase::class,
        ListVehicleCrmRelationsUseCaseInterface::class => ListVehicleCrmRelationsUseCase::class,
        ShowVehicleForCrmUseCaseInterface::class => ShowVehicleForCrmUseCase::class,
        SearchVehiclesForCrmUseCaseInterface::class => SearchVehiclesForCrmUseCase::class,
        ListEnginesForCrmUseCaseInterface::class => ListEnginesForCrmUseCase::class,
        ShowEngineForCrmUseCaseInterface::class => ShowEngineForCrmUseCase::class,
        ListManufacturersForCrmUseCaseInterface::class => ListManufacturersForCrmUseCase::class,
        ShowManufacturerForCrmUseCaseInterface::class => ShowManufacturerForCrmUseCase::class,
        StartManufacturerMutationUseCaseInterface::class => StartManufacturerMutationUseCase::class,
        CreateManufacturerUseCaseInterface::class => CreateManufacturerUseCase::class,
        UpdateManufacturerUseCaseInterface::class => UpdateManufacturerUseCase::class,
        DeleteManufacturerUseCaseInterface::class => DeleteManufacturerUseCase::class,
        ListManufacturersForCatalogUseCaseInterface::class => ListManufacturersForCatalogUseCase::class,
        StartEngineMutationUseCaseInterface::class => StartEngineMutationUseCase::class,
        CreateEngineUseCaseInterface::class => CreateEngineUseCase::class,
        UpdateEngineUseCaseInterface::class => UpdateEngineUseCase::class,
        DeleteEngineUseCaseInterface::class => DeleteEngineUseCase::class,
        StartModificationMutationUseCaseInterface::class => StartModificationMutationUseCase::class,
        CreateModificationUseCaseInterface::class => CreateModificationUseCase::class,
        UpdateModificationUseCaseInterface::class => UpdateModificationUseCase::class,
        DeleteModificationUseCaseInterface::class => DeleteModificationUseCase::class,
        ListVehicleModificationsForCatalogUseCaseInterface::class => ListVehicleModificationsForCatalogUseCase::class,
        ShowModificationForCatalogUseCaseInterface::class => ShowModificationForCatalogUseCase::class,
        StartPartSpecificationMutationUseCaseInterface::class => StartPartSpecificationMutationUseCase::class,
        CreatePartSpecificationUseCaseInterface::class => CreatePartSpecificationUseCase::class,
        UpdatePartSpecificationUseCaseInterface::class => UpdatePartSpecificationUseCase::class,
        DeletePartSpecificationUseCaseInterface::class => DeletePartSpecificationUseCase::class,
    ];

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
        CatalogMutationResultServiceInterface::class => CatalogMutationResultService::class,
        VehiclePartSpecificationOwnerResolverInterface::class => VehiclePartSpecificationOwnerResolver::class,
        EnginePartSpecificationOwnerResolverInterface::class => EnginePartSpecificationOwnerResolver::class,
        VehicleMutationWritePolicyInterface::class => VehicleMutationWritePolicy::class,
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
        foreach (self::USE_CASE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

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
