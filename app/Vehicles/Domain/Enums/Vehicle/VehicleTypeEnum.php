<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums\Vehicle;

use App\Vehicles\Traits\EnumHelperTrait;

enum VehicleTypeEnum: string
{
    use EnumHelperTrait;

    case PC = 'PC';
    case CV = 'CV';
    case MB = 'MB';
}
