<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ApplicabilityServiceFactoryInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\KitApplicabilityCalculatorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityKitResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

final readonly class KitApplicabilityCalculator implements KitApplicabilityCalculatorInterface
{
    public function __construct(
        private ApplicabilityServiceFactoryInterface $factory,
    ) {}

    public function calculate(KitData $kit): ?KitApplicabilityKitResultDTO
    {
        if ($kit->template === null) {
            return null;
        }

        $service = $this->factory->make($kit->template);

        return $service?->calculate($kit);
    }
}
