<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

use App\Modules\Applicability\Features\Catalog\Domain\Enums\ApplicabilityLookupStatusEnum;
use Illuminate\Support\Collection;

/**
 * Результат проверки применяемости номенклатуры для выбранной модификации.
 */
final readonly class ApplicabilityCheckResultDTO
{
    /** @param Collection<int, ApplicabilityEvidenceDTO> $evidence */
    public function __construct(
        public string $partNumber,
        public int $modificationId,
        public ApplicabilityLookupStatusEnum $status,
        public Collection $evidence,
    ) {}

    /**
     * Преобразует подтверждённый результат в публичную HTTP-проекцию.
     *
     * @return array{part_number: string, modification_id: int, status: string, evidence: list<array<string, int|string|null>>}
     */
    public function toArray(): array
    {
        return [
            'part_number' => $this->partNumber,
            'modification_id' => $this->modificationId,
            'status' => $this->status->value,
            'evidence' => $this->evidence
                ->map(static fn (ApplicabilityEvidenceDTO $evidence): array => $evidence->toArray())
                ->values()
                ->all(),
        ];
    }
}
