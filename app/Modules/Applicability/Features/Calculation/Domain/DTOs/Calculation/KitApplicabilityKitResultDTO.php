<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

/**
 * @param  array<int, int>  $targetIds
 */
final readonly class KitApplicabilityKitResultDTO
{
    public function __construct(
        public int $kitId,
        public KitApplicabilityAlgorithmEnum $algorithm,
        public ApplicabilityTargetTypeEnum $targetType,
        public array $targetIds,
    ) {}
}
