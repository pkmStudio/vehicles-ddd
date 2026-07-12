<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Infrastructure\Providers;

use App\Warehouse\Packaging\Application\Services\PackagingService;
use App\Warehouse\Packaging\Application\Services\TypeTemplateResolver;
use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\PackagingServiceInterface;
use App\Warehouse\Packaging\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Packaging\Infrastructure\Commands\PackDimensionCommand;
use App\Warehouse\Packaging\Infrastructure\Repositories\PackDimensionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse Packaging.
 */
final class PackagingServiceProvider extends ServiceProvider
{
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
     */
    public function register(): void
    {
        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind(abstract: $interface, concrete: $implementation);
        }

        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind(abstract: $interface, concrete: $implementation);
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(abstract: $interface, concrete: $implementation);
        }
    }
}
