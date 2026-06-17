<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\Filter;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum FormEnum: string
{
    use EnumHelperTrait;

    case RECTANGLE = 'Прямоугольник';
    case CYLINDER = 'Цилиндр';
    case TRAPEZOID = 'Трапеция';
}
