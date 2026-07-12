<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Factories;

use App\Vehicles\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationOwnerResolverInterface;
use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Выбирает resolver владельца PartSpecification по типу partable.
 */
final readonly class PartSpecificationOwnerResolverFactory implements PartSpecificationOwnerResolverFactoryInterface
{
    /**
     * Инициализирует зависимости класса через контейнер.
     */
    public function __construct(
        private VehiclePartSpecificationOwnerResolverInterface $vehicleResolver,
        private EnginePartSpecificationOwnerResolverInterface $engineResolver,
    ) {}

    /**
     * Выбирает resolver владельца спеки по partable type.
     */
    public function make(PartableTypeEnum $type): PartSpecificationOwnerResolverInterface
    {
        return match ($type) {
            PartableTypeEnum::VEHICLE => $this->vehicleResolver,
            PartableTypeEnum::ENGINE => $this->engineResolver,
        };
    }
}
