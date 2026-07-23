<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Enums;

/**
 * Стабильный алгоритм, которым была получена запись применяемости.
 */
enum KitApplicabilityAlgorithmEnum: string
{
    case WIPER = 'wiper';
    case MANUAL_XLSX = 'manual_xlsx';
}
