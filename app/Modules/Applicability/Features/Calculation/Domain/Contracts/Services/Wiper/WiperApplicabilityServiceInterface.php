<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WiperApplicabilityServiceInterface
{
    public function calculate(KitData $kit): KitApplicabilityKitResultDTO;
}
