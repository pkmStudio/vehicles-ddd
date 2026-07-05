<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Engine;

use App\Vehicles\Shared\Domain\Traits\EnumHelperTrait;

enum EngineTypeEnum: string
{
    use EnumHelperTrait;

    case PETROL = 'Бензиновый двигатель';
    case PETROL_TWO_STROKE = 'Бензиновый двигатель (двухтактный)';
    case DIESEL = 'Дизель';
    case PLUG_IN_HYBRID_DRIVE = 'Гибридный привод с подзарядкой';
    case ROTOR = 'Двигатель Ванкеля';
    case MILD_HYBRID_DRIVE = 'Мягкий гибридный привод';
    case FULL_HYBRID_DRIVE = 'Полный гибридный привод';
    case POWER_RESERVE_EXTENSION_DEVICE = 'Устройство увеличения запаса хода';
    case ELECTRO = 'Электродвигатель';
}
