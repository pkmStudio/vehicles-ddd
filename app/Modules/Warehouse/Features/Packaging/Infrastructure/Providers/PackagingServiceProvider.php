<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Packaging\Application\Services\PackagingService;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\AirFilterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\BrakePadsPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\CabinFilterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\GenericPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\OilFilterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\SparkPlugsPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\WiperAdapterPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies\WiperPackagingStrategy;
use App\Modules\Warehouse\Features\Packaging\Application\Services\TypeTemplateResolver;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Packaging\Infrastructure\Commands\PackDimensionCommand;
use App\Modules\Warehouse\Features\Packaging\Infrastructure\Repositories\PackDimensionRepository;
use App\Modules\Warehouse\Shared\Infrastructure\Logging\LaravelLoggerProxy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

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
                $makeStrategy = fn (string $strategy): PackagingStrategyInterface => $app->make($strategy);

                return array_map(
                    $makeStrategy,
                    self::STRATEGY_CLASSES,
                );
            });

        $this->app
            ->when(concrete: BrakePadsPackagingStrategy::class)
            ->needs(abstract: LoggerInterface::class)
            ->give(implementation: LaravelLoggerProxy::class);

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
