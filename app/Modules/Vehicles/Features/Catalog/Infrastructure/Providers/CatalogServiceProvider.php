<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Catalog\Application\Factories\EngineMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\ManufacturerMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\ModificationMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\PartSpecificationMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\PartSpecificationOwnerResolverFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Factories\VehicleMutationRequestFactory;
use App\Modules\Vehicles\Features\Catalog\Application\Services\CatalogMutationResultService;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\EnginePartSpecificationOwnerResolver;
use App\Modules\Vehicles\Features\Catalog\Application\Services\PartSpecification\VehiclePartSpecificationOwnerResolver;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Engine\CreateEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Engine\DeleteEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Engine\StartEngineMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Engine\UpdateEngineUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Manufacturer\CreateManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Manufacturer\DeleteManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Manufacturer\ListManufacturersForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Manufacturer\StartManufacturerMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Manufacturer\UpdateManufacturerUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\CreateModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\DeleteModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\ListVehicleModificationsForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\ShowModificationForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\StartModificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Modification\UpdateModificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification\CreatePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification\DeletePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification\StartPartSpecificationMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\PartSpecification\UpdatePartSpecificationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\CreateVehicleUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\DeleteVehicleUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\ListManufacturerVehiclesForCatalogUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\ListVehiclesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\SearchVehiclesForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\ShowVehicleForCrmUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\StartVehicleMutationUseCase;
use App\Modules\Vehicles\Features\Catalog\Application\UseCases\Vehicle\UpdateVehicleUseCase;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\EngineMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\VehicleMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Engine\CreateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Engine\DeleteEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Engine\StartEngineMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Engine\UpdateEngineUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\StartModificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification\UpdateModificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\StartPartSpecificationMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\PartSpecification\UpdatePartSpecificationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ListVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\SearchVehiclesForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\ShowVehicleForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle\UpdateVehicleUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Cache\LaravelCatalogMutationCacheService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\EngineModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ManufacturerCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\ModificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\PartSpecificationCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands\VehicleCommand;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Notifications\RabbitMqCatalogMutationNotificationService;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\EngineRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\ManufacturerRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\ModificationRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\PartSpecificationRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrmReadRepository;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleRepository;
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
        ListVehiclesForCrmUseCaseInterface::class => ListVehiclesForCrmUseCase::class,
        ListManufacturerVehiclesForCatalogUseCaseInterface::class => ListManufacturerVehiclesForCatalogUseCase::class,
        ShowVehicleForCrmUseCaseInterface::class => ShowVehicleForCrmUseCase::class,
        SearchVehiclesForCrmUseCaseInterface::class => SearchVehiclesForCrmUseCase::class,
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
        VehicleMutationRequestFactoryInterface::class => VehicleMutationRequestFactory::class,
        ManufacturerMutationRequestFactoryInterface::class => ManufacturerMutationRequestFactory::class,
        EngineMutationRequestFactoryInterface::class => EngineMutationRequestFactory::class,
        ModificationMutationRequestFactoryInterface::class => ModificationMutationRequestFactory::class,
        PartSpecificationMutationRequestFactoryInterface::class => PartSpecificationMutationRequestFactory::class,
        PartSpecificationOwnerResolverFactoryInterface::class => PartSpecificationOwnerResolverFactory::class,
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
        VehicleCrmReadRepositoryInterface::class => VehicleCrmReadRepository::class,
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        CatalogMutationCacheServiceInterface::class => LaravelCatalogMutationCacheService::class,
        CatalogMutationNotificationServiceInterface::class => RabbitMqCatalogMutationNotificationService::class,
        CatalogMutationResultServiceInterface::class => CatalogMutationResultService::class,
        VehiclePartSpecificationOwnerResolverInterface::class => VehiclePartSpecificationOwnerResolver::class,
        EnginePartSpecificationOwnerResolverInterface::class => EnginePartSpecificationOwnerResolver::class,
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

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
