<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Enums;

enum ApplicabilitySourceEnum: string
{
    case CALCULATED = 'calculated';
    case IMPORTED = 'imported';
    case MANUAL = 'manual';
}
