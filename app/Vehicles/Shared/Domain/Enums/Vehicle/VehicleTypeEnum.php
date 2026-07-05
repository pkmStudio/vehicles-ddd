<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

use App\Vehicles\Shared\Domain\Traits\EnumHelperTrait;

enum VehicleTypeEnum: string
{
    use EnumHelperTrait;

    case PC = 'PC';
    case CV = 'CV';
    case MB = 'MB';
}
