<?php

declare(strict_types=1);

namespace App\Vehicles\Enums;

use App\Vehicles\Traits\EnumHelperTrait;

enum BrakeSystemTypeEnum: string
{
    use EnumHelperTrait;

    case DRUM = 'Барабанный тормозной механизм';
    case DISC = 'Дисковой тормозной механизм';
    case DISCS_DRUMS = 'Диски/барабаны';
}
