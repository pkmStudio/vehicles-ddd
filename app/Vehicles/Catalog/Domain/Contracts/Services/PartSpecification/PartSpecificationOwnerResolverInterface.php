<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Services\PartSpecification;

use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerResolutionDTO;

/**
 * Описывает общий resolver владельца PartSpecification.
 */
interface PartSpecificationOwnerResolverInterface
{
    /**
     * Разрешает владельца спеки во внутренний id записи.
     */
    public function execute(PartSpecificationOwnerDTO $owner): PartSpecificationOwnerResolutionDTO;
}
