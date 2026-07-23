<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands;

use App\Modules\Applicability\Shared\Domain\Enums\KitApplicabilityAlgorithmEnum;
use App\Modules\Applicability\Shared\Domain\Enums\ApplicabilityTargetTypeEnum;

interface KitApplicabilityCommandInterface
{
    /**
     * @param  array<int, int>  $targetIds
     */
    public function syncCalculatedTargets(
        int $kitId,
        ApplicabilityTargetTypeEnum $targetType,
        KitApplicabilityAlgorithmEnum $algorithm,
        array $targetIds,
    ): void;
}
