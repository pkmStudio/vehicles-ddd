<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\Filter;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum PerformanceEnum: string
{
    use EnumHelperTrait;

    case WIND_UP = 'Накручиваемый фильтр';
    case LONG_TERM = 'Долговременный фильтр';
    case DIRECT_FLOW = 'Прямоточный фильтр';
}
