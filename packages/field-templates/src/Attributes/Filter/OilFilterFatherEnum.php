<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\Filter;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum OilFilterFatherEnum: string
{
    use EnumHelperTrait;

    case SIZE_10 = '10';
    case SIZE_15 = '15';
    case SIZE_19 = '19';
    case SIZE_19_6 = '19.6';
    case SIZE_21 = '21';
    case SIZE_24 = '24';
    case SIZE_28 = '28';
    case SIZE_28_7 = '28.7';
    case SIZE_29 = '29';
    case SIZE_31 = '31';
    case SIZE_31_5 = '31.5';
    case SIZE_40 = '40';
}
