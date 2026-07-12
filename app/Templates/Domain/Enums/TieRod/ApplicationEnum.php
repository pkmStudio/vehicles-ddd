<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\TieRod;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Назначение рулевой тяги. В dan-center хранилось литеральным массивом — здесь типизировано. */
enum ApplicationEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case AXIAL_JOINT = 'Осевой шарнир';
    case ROD = 'Тяга';
}
