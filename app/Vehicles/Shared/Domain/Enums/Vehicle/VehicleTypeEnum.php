<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

use App\Vehicles\Shared\Domain\Traits\EnumHelperTrait;

enum VehicleTypeEnum: string
{
    use EnumHelperTrait;

    // Passenger Car — легковые
    case PC = 'PC';

    // Commercial Vehicle — коммерческий транспорт
    case CV = 'CV';

    // Motorbike — мототехника
    case MB = 'MB';
}
