<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Расположение номенклатуры на ТС. Общий для нескольких семейств шаблонов (не привязан к одному узлу). */
enum PositionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRONT = 'Передняя';
    case UNIVERSAL = 'Универсальная';
    case BACK = 'Задняя';
}
