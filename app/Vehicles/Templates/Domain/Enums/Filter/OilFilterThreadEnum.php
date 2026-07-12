<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums\Filter;

use App\Vehicles\Templates\Domain\Traits\EnumHelperTrait;
use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;

/** Резьба масляного фильтра (для performance=WIND_UP). */
enum OilFilterThreadEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case THREE_QUARTER_X16 = '3/4"X16';
    case THREE_QUARTER_16UNF = '3/4"-16UNF';
    case THREE_QUARTER_16UNF_2B = '3/4"-16UNF-2B';
    case M18X15 = 'M18x1.5';
    case M19_14BSP = 'M19.0 (3/4-14BSP)';
    case M19_16UNF = 'M19.0 (3/4-16UNF)';
    case M206_16UNF = 'M20.6 (13/16-16UNF)';
    case M20X10 = 'M20x1.0';
    case M20X15 = 'M20x1.5';
    case M22X15 = 'M22x1.5';
    case M24X15 = 'M24x1.5';
    case M254_12UNF = 'M25.4 (1-12UNF)';
    case M25_4_16UNF = 'M25.4 (1-16UNF)';
    case M26X15 = 'M26x1.5';
    case M27X15 = 'M27х1.5';
    case M286_16UNF = 'M28.6 (1-1/8-16UNF)';
    case M3175_11UNF = 'M31.75 (1-1/4-11UNF)';
    case M3175_XBSP = 'M31.75 (1-1/4-XBSP)';
    case M34_16UNF = 'M34.9 (1-3/8-16UNF)';
    case M36X15 = 'M36x1.5';
    case M36X20 = 'M36x2.0';
    case M381_12UNF = 'M38.1 (1-1/2-12UNF)';
    case M381_16UNF = 'M38.1 (1-1/2-16UNF)';
}
