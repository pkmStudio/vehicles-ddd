<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums\Wiper;

use App\Vehicles\Templates\Domain\Traits\EnumHelperTrait;
use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;

/** Тип крепления передних дворников. */
enum FrontAdapterTypeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case H = 'Крючок (Hook / J-Hook)';
    case S = 'Боковой штырь (Side pin)';
    case B = 'Узкая кнопка (Narrow Push Button)';
    case P = 'Кнопка (Push button)';
    case C = 'Клешня (Claw)';
    case T = 'Боковой зажим (Pinch tab)';
    case TP = 'TOP LOCK';
    case N = 'Штырь (Pin lock)';
    case R = 'Штыковой замок (Bayonet arm)';
    case G = 'MG (GWB071)';
    case F = 'Оригинальное (Special)';
    case L = 'DNTL1.1';
    case Y = 'DYTL1.1';
    case M = 'MBTL1.1';
    case V = 'VATL5.1';
    case W = 'AERO CLIP';
    case R83 = 'Bayonet Arm 8x3';
    case H83 = 'Крючок (Hook / J-Hook) 8x3';
}
