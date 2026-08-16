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

}
