<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

interface CalculateKitApplicabilityUseCaseInterface
{
    public function execute(?int $kitId = null, int $chunk = 1000, ?string $runId = null): KitApplicabilityCalculationResultDTO;
}
