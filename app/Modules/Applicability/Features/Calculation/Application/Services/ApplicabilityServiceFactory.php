<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ApplicabilityServiceFactoryInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

final readonly class ApplicabilityServiceFactory implements ApplicabilityServiceFactoryInterface
{
    public function __construct(
        private WiperApplicabilityServiceInterface $wiper,
    ) {}

    public function make(NomenclatureDetailTemplateEnum $template): ?WiperApplicabilityServiceInterface
    {
        return match ($template) {
            NomenclatureDetailTemplateEnum::WIPER => $this->wiper,
            default => null,
        };
    }
}
