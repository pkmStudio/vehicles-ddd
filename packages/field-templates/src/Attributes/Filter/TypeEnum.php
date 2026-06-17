<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\Filter;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum TypeEnum: string
{
    use EnumHelperTrait;

    case CARBON = 'Угольный';
    case DUST = 'Пылевой';
}
