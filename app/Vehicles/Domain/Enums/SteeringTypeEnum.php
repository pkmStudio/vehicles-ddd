<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

use App\Vehicles\Traits\EnumHelperTrait;

enum SteeringTypeEnum: string
{
    use EnumHelperTrait;

    case LEFT = 'Левый руль';
    case RIGHT = 'Правый руль';
    case BOTH = 'Левый + Правый';
}
