<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

enum SteeringTypeEnum: string
{
    case LEFT = 'Левый руль';
    case RIGHT = 'Правый руль';
    case BOTH = 'Левый + Правый';
}
