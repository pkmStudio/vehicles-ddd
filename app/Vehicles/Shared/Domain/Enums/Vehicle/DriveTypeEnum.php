<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

enum DriveTypeEnum: string
{
    case FWD = 'Привод на передние колеса';
    case RWD = 'Привод на задние колеса';
    case AWD = 'Привод на все колеса';
}
