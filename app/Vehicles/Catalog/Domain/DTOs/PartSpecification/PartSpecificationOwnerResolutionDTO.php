<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;

/**
 * Передает результат разрешения владельца PartSpecification.
 */
final readonly class PartSpecificationOwnerResolutionDTO
{
    /**
     * Инициализирует immutable-снимок результата owner resolver.
     */
    public function __construct(
        public ?ResolvedPartSpecificationOwnerDTO $owner,
        public ?CatalogMutationRejectReasonEnum $rejectReason = null,
    ) {}
}
