<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Filter;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/** Исполнение фильтра. `->name` — хранимый ключ в details, `->value` — лейбл для Excel. */
enum PerformanceEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case WIND_UP = 'Накручиваемый фильтр';
    case LONG_TERM = 'Долговременный фильтр';
    case DIRECT_FLOW = 'Прямоточный фильтр';
}
