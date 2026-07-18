<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Enums\Vehicle;

enum VehicleTypeEnum: string
{
    // Passenger Car — легковые
    case PC = 'PC';

    // Commercial Vehicle — коммерческий транспорт
    case CV = 'CV';

    // Motorbike — мототехника
    case MB = 'MB';
}
