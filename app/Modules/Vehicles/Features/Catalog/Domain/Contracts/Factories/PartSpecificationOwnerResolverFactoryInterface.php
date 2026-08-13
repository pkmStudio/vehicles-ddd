<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\PartSpecification\PartSpecificationOwnerResolverInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Описывает selector-фабрику resolver владельца PartSpecification.
 */
interface PartSpecificationOwnerResolverFactoryInterface
{
    /**
     * Выбирает resolver владельца спеки по partable type.
     *
     * Шаги:
     * 1) Сопоставить PartableTypeEnum с resolver-ом владельца.
     * 2) Вернуть resolver, который умеет найти или подготовить internal owner id.
     */
    public function make(PartableTypeEnum $type): PartSpecificationOwnerResolverInterface;
}
