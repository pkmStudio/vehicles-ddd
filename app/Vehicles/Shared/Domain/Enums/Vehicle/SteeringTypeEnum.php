<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

use App\Vehicles\Shared\Domain\Traits\EnumHelperTrait;

enum SteeringTypeEnum: string
{
    use EnumHelperTrait;

    case LEFT = 'Левый руль';
    case RIGHT = 'Правый руль';
    case BOTH = 'Левый + Правый';
}
