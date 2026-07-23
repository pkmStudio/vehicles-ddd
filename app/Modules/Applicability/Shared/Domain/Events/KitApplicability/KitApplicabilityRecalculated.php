<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Events\KitApplicability;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

final readonly class KitApplicabilityRecalculated
{
    public function __construct(
        public string $runId,
        public KitApplicabilityCalculationResultDTO $result,
    ) {}
}
