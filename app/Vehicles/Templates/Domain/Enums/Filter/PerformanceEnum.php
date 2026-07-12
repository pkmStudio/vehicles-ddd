<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums\Filter;

use App\Vehicles\Templates\Domain\Traits\EnumHelperTrait;
use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;

/** Исполнение фильтра. `->name` — хранимый ключ в details, `->value` — лейбл для Excel. */
enum PerformanceEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case WIND_UP = 'Накручиваемый фильтр';
    case LONG_TERM = 'Долговременный фильтр';
    case DIRECT_FLOW = 'Прямоточный фильтр';
}
