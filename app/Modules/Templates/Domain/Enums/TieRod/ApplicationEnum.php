<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\TieRod;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/** Назначение рулевой тяги. В dan-center хранилось литеральным массивом — здесь типизировано. */
enum ApplicationEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case AXIAL_JOINT = 'Осевой шарнир';
    case ROD = 'Тяга';
}
