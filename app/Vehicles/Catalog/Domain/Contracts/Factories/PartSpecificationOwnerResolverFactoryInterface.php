<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Factories;

use App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationOwnerResolverInterface;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Описывает selector-фабрику resolver владельца PartSpecification.
 */
interface PartSpecificationOwnerResolverFactoryInterface
{
    /**
     * Выбирает resolver владельца спеки по partable type.
     */
    public function make(PartableTypeEnum $type): PartSpecificationOwnerResolverInterface;
}
