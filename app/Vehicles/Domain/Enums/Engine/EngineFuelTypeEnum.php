<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums\Engine;

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

    /**
     * Нужны ли двигателю свечи зажигания: да — для бензина/газа/спирта/водорода и их
     * комбинаций (искровое зажигание); нет — для дизеля и электричества.
     */
    public function needsSparkPlugs(): bool
    {
        return match ($this) {
            self::PETROL, self::GAS, self::ALCOHOL, self::HYDROGEN,
            self::PETROL_ALCOHOL, self::PETROL_GAS, self::PETROL_ALCOHOL_GAS => true,
            self::DIESEL, self::ELECTRIC, self::DIESEL_ALCOHOL, self::DIESEL_GAS => false,
        };
    }
}
