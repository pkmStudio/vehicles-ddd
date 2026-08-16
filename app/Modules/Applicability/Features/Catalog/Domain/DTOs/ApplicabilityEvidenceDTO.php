<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

/**
 * Публичное подтверждение применяемости товара через конкретный комплект.
 */
final readonly class ApplicabilityEvidenceDTO
{
    public function __construct(
        public int $kitId,
        public string $targetType,
        public string $source,
        public ?string $algorithm,
    ) {}

}
