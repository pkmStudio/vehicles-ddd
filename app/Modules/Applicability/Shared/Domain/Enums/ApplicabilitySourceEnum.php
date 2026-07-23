<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Enums;

/**
 * Стабильный источник записи применяемости в `kit_applicabilities`.
 */
enum ApplicabilitySourceEnum: string
{
    case CALCULATED = 'calculated';
    case IMPORTED = 'imported';
    case MANUAL = 'manual';
}
