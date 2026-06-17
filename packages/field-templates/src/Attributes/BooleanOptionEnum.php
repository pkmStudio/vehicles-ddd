<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum BooleanOptionEnum: string
{
    use EnumHelperTrait;

    case TRUE = 'Да';
    case FALSE = 'Нет';
}
