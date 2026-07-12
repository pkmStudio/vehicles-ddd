<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Providers;

use App\Vehicles\Catalog\Application\Factories\EngineMutationRequestFactory;
use App\Vehicles\Catalog\Application\Factories\ManufacturerMutationRequestFactory;
use App\Vehicles\Catalog\Application\Factories\ModificationMutationRequestFactory;
use App\Vehicles\Catalog\Application\Factories\PartSpecificationMutationRequestFactory;
use App\Vehicles\Catalog\Application\Factories\PartSpecificationOwnerResolverFactory;
use App\Vehicles\Catalog\Application\Factories\VehicleMutationRequestFactory;
use App\Vehicles\Catalog\Application\Services\CatalogMutationCacheService;
use App\Vehicles\Catalog\Application\Services\CatalogMutationResultService;
use App\Vehicles\Catalog\Application\Services\PartSpecification\EnginePartSpecificationOwnerResolver;
use App\Vehicles\Catalog\Application\Services\PartSpecification\VehiclePartSpecificationOwnerResolver;
use App\Vehicles\Catalog\Application\UseCases\Engine\CreateEngineUseCase;
use App\Vehicles\Catalog\Application\UseCases\Engine\DeleteEngineUseCase;
use App\Vehicles\Catalog\Application\UseCases\Engine\StartEngineMutationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Engine\UpdateEngineUseCase;
use App\Vehicles\Catalog\Application\UseCases\Manufacturer\CreateManufacturerUseCase;
use App\Vehicles\Catalog\Application\UseCases\Manufacturer\DeleteManufacturerUseCase;
use App\Vehicles\Catalog\Application\UseCases\Manufacturer\StartManufacturerMutationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Manufacturer\UpdateManufacturerUseCase;
use App\Vehicles\Catalog\Application\UseCases\Modification\CreateModificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Modification\DeleteModificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Modification\StartModificationMutationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Modification\UpdateModificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\PartSpecification\CreatePartSpecificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\PartSpecification\DeletePartSpecificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\PartSpecification\StartPartSpecificationMutationUseCase;
use App\Vehicles\Catalog\Application\UseCases\PartSpecification\UpdatePartSpecificationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Vehicle\CreateVehicleUseCase;
use App\Vehicles\Catalog\Application\UseCases\Vehicle\DeleteVehicleUseCase;
use App\Vehicles\Catalog\Application\UseCases\Vehicle\StartVehicleMutationUseCase;
use App\Vehicles\Catalog\Application\UseCases\Vehicle\UpdateVehicleUseCase;
use App\Vehicles\Catalog\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\EngineMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\ManufacturerMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\ModificationMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Factories\VehicleMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationNotificationServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\CatalogMutationResultServiceInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\CreateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\DeleteEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\StartEngineMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine\UpdateEngineUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\CreateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\DeleteManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\StartManufacturerMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer\UpdateManufacturerUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\CreateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\DeleteModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\StartModificationMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Modification\UpdateModificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification\CreatePartSpecificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification\DeletePartSpecificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification\StartPartSpecificationMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\PartSpecification\UpdatePartSpecificationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\CreateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\DeleteVehicleUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\StartVehicleMutationUseCaseInterface;
use App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle\UpdateVehicleUseCaseInterface;
use App\Vehicles\Catalog\Infrastructure\Commands\EngineCommand;
use App\Vehicles\Catalog\Infrastructure\Commands\ManufacturerCommand;
use App\Vehicles\Catalog\Infrastructure\Commands\ModificationCommand;
use App\Vehicles\Catalog\Infrastructure\Commands\PartSpecificationCommand;
use App\Vehicles\Catalog\Infrastructure\Commands\VehicleCommand;
use App\Vehicles\Catalog\Infrastructure\Notifications\RabbitMqCatalogMutationNotificationService;
use App\Vehicles\Catalog\Infrastructure\Repositories\EngineRepository;
use App\Vehicles\Catalog\Infrastructure\Repositories\ManufacturerRepository;
use App\Vehicles\Catalog\Infrastructure\Repositories\ModificationRepository;
use App\Vehicles\Catalog\Infrastructure\Repositories\PartSpecificationRepository;
use App\Vehicles\Catalog\Infrastructure\Repositories\VehicleRepository;
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
        StartManufacturerMutationUseCaseInterface::class => StartManufacturerMutationUseCase::class,
        CreateManufacturerUseCaseInterface::class => CreateManufacturerUseCase::class,
        UpdateManufacturerUseCaseInterface::class => UpdateManufacturerUseCase::class,
        DeleteManufacturerUseCaseInterface::class => DeleteManufacturerUseCase::class,
        StartEngineMutationUseCaseInterface::class => StartEngineMutationUseCase::class,
        CreateEngineUseCaseInterface::class => CreateEngineUseCase::class,
        UpdateEngineUseCaseInterface::class => UpdateEngineUseCase::class,
        DeleteEngineUseCaseInterface::class => DeleteEngineUseCase::class,
        StartModificationMutationUseCaseInterface::class => StartModificationMutationUseCase::class,
        CreateModificationUseCaseInterface::class => CreateModificationUseCase::class,
        UpdateModificationUseCaseInterface::class => UpdateModificationUseCase::class,
        DeleteModificationUseCaseInterface::class => DeleteModificationUseCase::class,
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
        ModificationCommandInterface::class => ModificationCommand::class,
        PartSpecificationCommandInterface::class => PartSpecificationCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        VehicleRepositoryInterface::class => VehicleRepository::class,
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        CatalogMutationCacheServiceInterface::class => CatalogMutationCacheService::class,
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
