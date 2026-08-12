<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationOwnerResolverFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\EnginePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\VehiclePartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Выбирает resolver владельца PartSpecification по типу partable.
 */
final readonly class PartSpecificationOwnerResolverFactory implements PartSpecificationOwnerResolverFactoryInterface
{
    /**
     * Получает resolver-ы для поддерживаемых partable owner типов.
     *
     * Шаги:
     * 1) Принять resolver владельца-автомобиля.
     * 2) Принять resolver владельца-двигателя.
     * 3) Сохранить оба resolver-а для выбора по PartableTypeEnum.
     */
    public function __construct(
        private VehiclePartSpecificationOwnerResolverInterface $vehicleResolver,
        private EnginePartSpecificationOwnerResolverInterface $engineResolver,
    ) {}

    /**
     * Выбирает resolver владельца спеки по partable type.
     *
     * Шаги:
     * 1) Сопоставить тип владельца с конкретным application resolver-ом.
     * 2) Вернуть resolver, который знает, как найти или создать соответствующего владельца.
     */
    public function make(PartableTypeEnum $type): PartSpecificationOwnerResolverInterface
    {
        return match ($type) {
            PartableTypeEnum::VEHICLE => $this->vehicleResolver,
            PartableTypeEnum::ENGINE => $this->engineResolver,
        };
    }
}
