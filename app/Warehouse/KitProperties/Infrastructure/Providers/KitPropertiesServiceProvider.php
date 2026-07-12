<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Infrastructure\Providers;

use App\Warehouse\KitProperties\Application\Services\KitComplectationService;
use App\Warehouse\KitProperties\Application\Services\KitPropertiesService;
use App\Warehouse\KitProperties\Application\Services\Strategies\SingleTypeStrategy;
use App\Warehouse\KitProperties\Application\Services\Strategies\WiperWithAdapterStrategy;
use App\Warehouse\KitProperties\Application\Services\TypeTemplateResolver;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitComplectationServiceInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\KitPropertiesServiceInterface;
use App\Warehouse\KitProperties\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
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
    private const array SERVICE_BINDINGS = [
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        KitComplectationServiceInterface::class => KitComplectationService::class,
    ];

    public function register(): void
    {
        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(abstract: $interface, concrete: $implementation);
        }

        $this->app->bind(
            abstract: KitPropertiesServiceInterface::class,
            concrete: function (Application $app): KitPropertiesService {
                return new KitPropertiesService(
                    packaging: $app->make(PackagingServiceInterface::class),
                    complectationService: $app->make(KitComplectationServiceInterface::class),
                    strategies: [
                        $app->make(WiperWithAdapterStrategy::class),
                        $app->make(SingleTypeStrategy::class), // fallback, всегда регистрируем последней
                    ],
                );
            },
        );
    }
}
