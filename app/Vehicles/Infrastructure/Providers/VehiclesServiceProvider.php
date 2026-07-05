<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Infrastructure\Repositories\EngineRepository;
use App\Vehicles\Infrastructure\Repositories\FeatureRepository;
use App\Vehicles\Infrastructure\Repositories\FeatureValueRepository;
use App\Vehicles\Infrastructure\Repositories\ManufacturerRepository;
use App\Vehicles\Infrastructure\Repositories\ModificationRepository;
use App\Vehicles\Infrastructure\Repositories\PartSpecificationRepository;
use App\Vehicles\Infrastructure\Repositories\VehicleRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository-порты (чтение) над общими Domain\Models — теперь используются только Export
 * (Import с plan.md §3 получил свою копию моделей + Repository, возвращающую <Entity>Data,
 * в ImportServiceProvider). Разделится окончательно и исчезнет, когда аналогичный переезд на
 * spatie/laravel-data пройдёт и для Export. Command (запись) сюда не входит — это
 * исключительно Import, биндинги в ImportServiceProvider.
 */
class VehiclesServiceProvider extends ServiceProvider
{
    private const array REPOSITORY_BINDINGS = [
        ManufacturerRepositoryInterface::class => ManufacturerRepository::class,
        VehicleRepositoryInterface::class => VehicleRepository::class,
        ModificationRepositoryInterface::class => ModificationRepository::class,
        EngineRepositoryInterface::class => EngineRepository::class,
        PartSpecificationRepositoryInterface::class => PartSpecificationRepository::class,
        FeatureRepositoryInterface::class => FeatureRepository::class,
        FeatureValueRepositoryInterface::class => FeatureValueRepository::class,
    ];

    public function register(): void
    {
        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
