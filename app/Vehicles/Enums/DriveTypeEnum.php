<?php

declare(strict_types=1);

namespace App\Vehicles\Enums;

use App\Vehicles\Traits\EnumHelperTrait;

enum DriveTypeEnum: string
{
    use EnumHelperTrait;

    case FWD = 'Привод на передние колеса';
    case RWD = 'Привод на задние колеса';
    case AWD = 'Привод на все колеса';
}
