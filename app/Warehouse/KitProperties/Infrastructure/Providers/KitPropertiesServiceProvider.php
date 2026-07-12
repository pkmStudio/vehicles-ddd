<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Infrastructure\Providers;

use App\Warehouse\KitProperties\Application\Services\KitComplectationService;
use App\Warehouse\KitProperties\Application\Services\KitPropertiesService;
use App\Warehouse\KitProperties\Application\Services\Strategies\SingleTypeStrategy;
use App\Warehouse\KitProperties\Application\Services\Strategies\WiperWithAdapterStrategy;
use App\Warehouse\KitProperties\Application\Services\TypeTemplateResolver;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitCompositionStrategyInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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

    /**
     * Биндит сервисы KitProperties и передаёт упорядоченный список стратегий через контейнер.
     */
    public function register(): void
    {
        $this->app
            ->when(concrete: KitPropertiesService::class)
            ->needs(abstract: '$strategies')
            ->give(implementation: function (Application $app): array {
                return array_map(
                    fn (string $strategy): KitCompositionStrategyInterface => $app->make($strategy),
                    self::COMPOSITION_STRATEGY_CLASSES,
                );
            });

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(abstract: $interface, concrete: $implementation);
        }

        $this->app->bind(
            abstract: KitPropertiesServiceInterface::class,
            concrete: KitPropertiesService::class,
        );
    }
}
