<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums;

use App\Templates\Domain\Contracts\EnumHelperInterface;
use App\Templates\Domain\Traits\EnumHelperTrait;

/** Расположение номенклатуры на ТС. Общий для нескольких семейств шаблонов (не привязан к одному узлу). */
enum PositionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRONT = 'Переднее';
    case UNIVERSAL = 'Универсальное';
    case BACK = 'Заднее';
}
