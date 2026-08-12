<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation;

use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;
use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;

/**
 * @param  array<int, int>  $targetIds
 */
final readonly class KitApplicabilityKitResultDTO
{
    /**
     * Создает итог расчета одного комплекта.
     */
    public function __construct(
        public int $kitId,
        public KitApplicabilityAlgorithmEnum $algorithm,
        public ApplicabilityTargetTypeEnum $targetType,
        public array $targetIds,
    ) {}
}
