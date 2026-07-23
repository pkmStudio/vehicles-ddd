<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;

interface CalculationFailureReporterInterface
{
    public function store(KitApplicabilityCalculationResultDTO $result): ?string;
}
