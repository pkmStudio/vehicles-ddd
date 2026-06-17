<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum PositionEnum: string
{
    use EnumHelperTrait;

    case FRONT = 'Переднее';
    case UNIVERSAL = 'Универсальное';
    case BACK = 'Заднее';
}
