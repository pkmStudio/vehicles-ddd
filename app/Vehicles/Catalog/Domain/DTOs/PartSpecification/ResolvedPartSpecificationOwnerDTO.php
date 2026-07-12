<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Передает уже разрешенного владельца PartSpecification с внутренним id записи.
 */
final readonly class ResolvedPartSpecificationOwnerDTO
{
    /**
     * Инициализирует immutable-снимок разрешенного владельца спеки.
     */
    public function __construct(
        public PartableTypeEnum $type,
        public int $externalId,
        public int $partableId,
    ) {}
}
