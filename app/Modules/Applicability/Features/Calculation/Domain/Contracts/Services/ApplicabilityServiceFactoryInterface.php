<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;

interface ApplicabilityServiceFactoryInterface
{
    public function make(NomenclatureDetailTemplateEnum $template): ?WiperApplicabilityServiceInterface;
}
