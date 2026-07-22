<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Infrastructure\Providers;

use App\Modules\Warehouse\Features\KitProperties\Application\Services\KitComplectationService;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\KitPropertiesService;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies\SingleTypeStrategy;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\Strategies\WiperWithAdapterStrategy;
use App\Modules\Warehouse\Features\KitProperties\Application\Services\TypeTemplateResolver;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients\PackagingClientInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\KitProperties\Infrastructure\Clients\PackagingClient;
use App\Modules\Warehouse\Shared\Infrastructure\Logging\LaravelLoggerProxy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse KitProperties. `KitPropertiesService`
 * биндится через closure (не через `*_BINDINGS`-константу), т.к. её конструктор принимает явный
 * упорядоченный массив стратегий состава — порядок важен (`WiperWithAdapterStrategy` обязана идти
 * раньше fallback-`SingleTypeStrategy`), как и в dan-center `KitServiceProvider`.
 */
final class KitPropertiesServiceProvider extends ServiceProvider
{
    private const array COMPOSITION_STRATEGY_CLASSES = [
        WiperWithAdapterStrategy::class,
        SingleTypeStrategy::class,
    ];

    private const array SERVICE_BINDINGS = [
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        KitComplectationServiceInterface::class => KitComplectationService::class,
    ];

    private const array CLIENT_BINDINGS = [
        PackagingClientInterface::class => PackagingClient::class,
    ];

    /**
     * Биндит сервисы KitProperties и передаёт упорядоченный список стратегий через контейнер.
     *
     * Шаги:
     * 1) Передать KitPropertiesService упорядоченный список стратегий состава.
     * 2) Зарегистрировать обычные service-биндинги и основной KitPropertiesService.
     */
    public function register(): void
    {
        $this->app
            ->when(concrete: KitPropertiesService::class)
            ->needs(abstract: '$strategies')
            ->give(implementation: function (Application $app): array {
                $makeStrategy = fn (string $strategy): KitCompositionStrategyInterface => $app->make($strategy);

                return array_map(
                    $makeStrategy,
                    self::COMPOSITION_STRATEGY_CLASSES,
                );
            });

        $this->app
            ->when(concrete: KitPropertiesService::class)
            ->needs(abstract: LoggerInterface::class)
            ->give(implementation: LaravelLoggerProxy::class);

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

        $this->app->bind(
            abstract: KitPropertiesServiceInterface::class,
            concrete: KitPropertiesService::class,
        );
    }
}
