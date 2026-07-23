<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Enums;

enum CalculationCompletionStatusEnum: string
{
    case COMPLETED = 'completed';
    case COMPLETED_WITH_FAILURES = 'completed_with_failures';
}
