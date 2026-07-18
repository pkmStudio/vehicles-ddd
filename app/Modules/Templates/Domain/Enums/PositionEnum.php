<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/** Расположение номенклатуры на ТС. Общий для нескольких семейств шаблонов (не привязан к одному узлу). */
enum PositionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRONT = 'Переднее';
    case UNIVERSAL = 'Универсальное';
    case BACK = 'Заднее';
}
