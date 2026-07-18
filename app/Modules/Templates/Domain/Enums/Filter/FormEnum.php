<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Filter;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/** Форма фильтра. `->name` — хранимый ключ в details, `->value` — лейбл для Excel. */
enum FormEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case RECTANGLE = 'Прямоугольник';
    case CYLINDER = 'Цилиндр';
    case TRAPEZOID = 'Трапеция';
}
