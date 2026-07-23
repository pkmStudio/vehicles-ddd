<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Shared\Domain\Enums;

/**
 * Стабильный тип цели применяемости без привязки к Eloquent class-string.
 */
enum ApplicabilityTargetTypeEnum: string
{
    case ENGINE = 'engine';
    case MODIFICATION = 'modification';
    case PART_SPECIFICATION = 'part_specification';
}
