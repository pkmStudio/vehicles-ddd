<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

use App\Vehicles\Traits\EnumHelperTrait;

enum EngineFuelTypeEnum: string
{
    use EnumHelperTrait;

    // Одиночные типы топлива
    case PETROL = 'бензин';
    case DIESEL = 'Дизель';
    case GAS = 'Газ';
    case ALCOHOL = 'Спирт';
    case HYDROGEN = 'Водород';
    case ELECTRIC = 'электричество';

    // Комбинированные типы (битопливные)
    case PETROL_ALCOHOL = 'Бензин/спирт';
    case DIESEL_ALCOHOL = 'Дизель/спирт';
    case DIESEL_GAS = 'Дизель/газ';
    case PETROL_GAS = 'Бензин/газ';

    // Мультитопливные (три и более)
    case PETROL_ALCOHOL_GAS = 'Бензин / этиловый спирт / природный газ';
}
