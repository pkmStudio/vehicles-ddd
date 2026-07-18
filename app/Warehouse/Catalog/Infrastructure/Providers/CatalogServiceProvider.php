<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Providers;

use App\Warehouse\Catalog\Application\Factories\BrandMutationRequestFactory;
use App\Warehouse\Catalog\Application\Factories\KitMutationRequestFactory;
use App\Warehouse\Catalog\Application\Factories\NomenclatureMutationRequestFactory;
use App\Warehouse\Catalog\Application\Factories\PackDimensionMutationRequestFactory;
use App\Warehouse\Catalog\Application\Services\WarehouseCatalogMutationCacheService;
use App\Warehouse\Catalog\Application\Services\WarehouseCatalogMutationResultService;
use App\Warehouse\Catalog\Application\UseCases\Brand\CreateBrandUseCase;
use App\Warehouse\Catalog\Application\UseCases\Brand\DeleteBrandUseCase;
use App\Warehouse\Catalog\Application\UseCases\Brand\StartBrandMutationUseCase;
use App\Warehouse\Catalog\Application\UseCases\Brand\UpdateBrandUseCase;
use App\Warehouse\Catalog\Application\UseCases\Kit\CreateKitUseCase;
use App\Warehouse\Catalog\Application\UseCases\Kit\DeleteKitUseCase;
use App\Warehouse\Catalog\Application\UseCases\Kit\StartKitMutationUseCase;
use App\Warehouse\Catalog\Application\UseCases\Kit\UpdateKitUseCase;
use App\Warehouse\Catalog\Application\UseCases\Nomenclature\CreateNomenclatureUseCase;
use App\Warehouse\Catalog\Application\UseCases\Nomenclature\DeleteNomenclatureUseCase;
use App\Warehouse\Catalog\Application\UseCases\Nomenclature\StartNomenclatureMutationUseCase;
use App\Warehouse\Catalog\Application\UseCases\Nomenclature\UpdateNomenclatureUseCase;
use App\Warehouse\Catalog\Application\UseCases\PackDimension\CreatePackDimensionUseCase;
use App\Warehouse\Catalog\Application\UseCases\PackDimension\DeletePackDimensionUseCase;
use App\Warehouse\Catalog\Application\UseCases\PackDimension\StartPackDimensionMutationUseCase;
use App\Warehouse\Catalog\Application\UseCases\PackDimension\UpdatePackDimensionUseCase;
use App\Warehouse\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Catalog\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Warehouse\Catalog\Domain\Contracts\Factories\BrandMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Factories\KitMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Factories\NomenclatureMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Factories\PackDimensionMutationRequestFactoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationCacheServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationNotificationServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\Services\WarehouseCatalogMutationResultServiceInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\CreateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\DeleteBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\StartBrandMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Brand\UpdateBrandUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\CreateKitUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\DeleteKitUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\StartKitMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Kit\UpdateKitUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\CreateNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\DeleteNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\StartNomenclatureMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\Nomenclature\UpdateNomenclatureUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\CreatePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\DeletePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\StartPackDimensionMutationUseCaseInterface;
use App\Warehouse\Catalog\Domain\Contracts\UseCases\PackDimension\UpdatePackDimensionUseCaseInterface;
use App\Warehouse\Catalog\Infrastructure\Commands\BrandCommand;
use App\Warehouse\Catalog\Infrastructure\Commands\KitCommand;
use App\Warehouse\Catalog\Infrastructure\Commands\NomenclatureCommand;
use App\Warehouse\Catalog\Infrastructure\Commands\PackDimensionCommand;
use App\Warehouse\Catalog\Infrastructure\Clients\KitPropertiesClient;
use App\Warehouse\Catalog\Infrastructure\Notifications\RabbitMqWarehouseCatalogMutationNotificationService;
use App\Warehouse\Catalog\Infrastructure\Repositories\BrandRepository;
use App\Warehouse\Catalog\Infrastructure\Repositories\KitRepository;
use App\Warehouse\Catalog\Infrastructure\Repositories\NomenclatureRepository;
use App\Warehouse\Catalog\Infrastructure\Repositories\PackDimensionRepository;
use App\Warehouse\Catalog\Infrastructure\Repositories\TypeRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги фичи Warehouse Catalog.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    private const array USE_CASE_BINDINGS = [
        StartBrandMutationUseCaseInterface::class => StartBrandMutationUseCase::class,
        CreateBrandUseCaseInterface::class => CreateBrandUseCase::class,
        UpdateBrandUseCaseInterface::class => UpdateBrandUseCase::class,
        DeleteBrandUseCaseInterface::class => DeleteBrandUseCase::class,
        StartNomenclatureMutationUseCaseInterface::class => StartNomenclatureMutationUseCase::class,
        CreateNomenclatureUseCaseInterface::class => CreateNomenclatureUseCase::class,
        UpdateNomenclatureUseCaseInterface::class => UpdateNomenclatureUseCase::class,
        DeleteNomenclatureUseCaseInterface::class => DeleteNomenclatureUseCase::class,
        StartPackDimensionMutationUseCaseInterface::class => StartPackDimensionMutationUseCase::class,
        CreatePackDimensionUseCaseInterface::class => CreatePackDimensionUseCase::class,
        UpdatePackDimensionUseCaseInterface::class => UpdatePackDimensionUseCase::class,
        DeletePackDimensionUseCaseInterface::class => DeletePackDimensionUseCase::class,
        StartKitMutationUseCaseInterface::class => StartKitMutationUseCase::class,
        CreateKitUseCaseInterface::class => CreateKitUseCase::class,
        UpdateKitUseCaseInterface::class => UpdateKitUseCase::class,
        DeleteKitUseCaseInterface::class => DeleteKitUseCase::class,
    ];

    private const array FACTORY_BINDINGS = [
        BrandMutationRequestFactoryInterface::class => BrandMutationRequestFactory::class,
        NomenclatureMutationRequestFactoryInterface::class => NomenclatureMutationRequestFactory::class,
        PackDimensionMutationRequestFactoryInterface::class => PackDimensionMutationRequestFactory::class,
        KitMutationRequestFactoryInterface::class => KitMutationRequestFactory::class,
    ];

    private const array COMMAND_BINDINGS = [
        BrandCommandInterface::class => BrandCommand::class,
        NomenclatureCommandInterface::class => NomenclatureCommand::class,
        PackDimensionCommandInterface::class => PackDimensionCommand::class,
        KitCommandInterface::class => KitCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        BrandRepositoryInterface::class => BrandRepository::class,
        TypeRepositoryInterface::class => TypeRepository::class,
        NomenclatureRepositoryInterface::class => NomenclatureRepository::class,
        PackDimensionRepositoryInterface::class => PackDimensionRepository::class,
        KitRepositoryInterface::class => KitRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        WarehouseCatalogMutationCacheServiceInterface::class => WarehouseCatalogMutationCacheService::class,
        WarehouseCatalogMutationNotificationServiceInterface::class => RabbitMqWarehouseCatalogMutationNotificationService::class,
        WarehouseCatalogMutationResultServiceInterface::class => WarehouseCatalogMutationResultService::class,
    ];

    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
    ];

    /**
     * Регистрирует все биндинги фичи Warehouse Catalog в контейнере.
     *
     * Шаги:
     * 1) Зарегистрировать use cases, factories и commands CRUD-сценариев.
     * 2) Зарегистрировать repositories, services и outbound notification adapter.
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

        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
