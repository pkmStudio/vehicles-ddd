<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Infrastructure\Providers;

use App\Warehouse\Packaging\Application\Services\PackagingService;
use App\Warehouse\Packaging\Application\Services\Strategies\AirFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\BrakePadsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\CabinFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\GenericPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\OilFilterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\SparkPlugsPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperAdapterPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\Strategies\WiperPackagingStrategy;
use App\Warehouse\Packaging\Application\Services\TypeTemplateResolver;
use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Infrastructure\Commands\PackDimensionCommand;
use App\Warehouse\Packaging\Infrastructure\Repositories\PackDimensionRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse Packaging.
 */
final class PackagingServiceProvider extends ServiceProvider
{
    private const array STRATEGY_CLASSES = [
        BrakePadsPackagingStrategy::class,
        WiperPackagingStrategy::class,
        CabinFilterPackagingStrategy::class,
        OilFilterPackagingStrategy::class,
        SparkPlugsPackagingStrategy::class,
        WiperAdapterPackagingStrategy::class,
        AirFilterPackagingStrategy::class,
        GenericPackagingStrategy::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        PackDimensionRepositoryInterface::class => PackDimensionRepository::class,
    ];

    private const array COMMAND_BINDINGS = [
        PackDimensionCommandInterface::class => PackDimensionCommand::class,
    ];

    private const array SERVICE_BINDINGS = [
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        PackagingServiceInterface::class => PackagingService::class,
    ];

    /**
     * Биндит порты Packaging-фичи на инфраструктурные и прикладные реализации.
     *
     * Шаги:
     * 1) Передать PackagingService упорядоченный список стратегий подбора упаковки.
     * 2) Зарегистрировать repository, command и application-service биндинги фичи.
     */
    public function register(): void
    {
        $this->app
            ->when(concrete: PackagingService::class)
            ->needs(abstract: '$strategies')
            ->give(implementation: function (Application $app): array {
                return array_map(
                    fn (string $strategy): PackagingStrategyInterface => $app->make($strategy),
                    self::STRATEGY_CLASSES,
                );
            });

        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
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

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
