<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Enums;

enum KitApplicabilityAlgorithmEnum: string
{
    case WIPER = 'wiper';
    case MANUAL_XLSX = 'manual_xlsx';
}
