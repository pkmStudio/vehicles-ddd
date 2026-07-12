<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Передает владельца PartSpecification во внешнем контракте мутации.
 */
final readonly class PartSpecificationOwnerDTO
{
    /**
     * Инициализирует immutable-снимок владельца спеки.
     */
    public function __construct(
        public PartableTypeEnum $type,
        public int $externalId,
        public ?PartSpecificationOwnerVehicleDTO $vehicle = null,
        public ?PartSpecificationOwnerEngineDTO $engine = null,
    ) {}
}
