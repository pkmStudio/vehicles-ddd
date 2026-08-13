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

    /**
     * Преобразует подтверждение в snake_case HTTP-проекцию.
     *
     * @return array{kit_id: int, target_type: string, source: string, algorithm: ?string}
     */
    public function toArray(): array
    {
        return [
            'kit_id' => $this->kitId,
            'target_type' => $this->targetType,
            'source' => $this->source,
            'algorithm' => $this->algorithm,
        ];
    }
}
