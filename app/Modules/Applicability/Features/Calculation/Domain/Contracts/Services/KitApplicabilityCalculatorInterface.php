<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface KitApplicabilityCalculatorInterface
{
    public function calculate(KitData $kit): ?KitApplicabilityKitResultDTO;
}
