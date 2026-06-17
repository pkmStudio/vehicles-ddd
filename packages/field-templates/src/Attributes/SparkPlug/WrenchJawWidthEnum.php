<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\SparkPlug;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum WrenchJawWidthEnum: string
{
    use EnumHelperTrait;

    case WJ14 = '14';
    case WJ16 = '16';
    case WJ19 = '19';
    case WJ20 = '20';
    case WJ208 = '20.8';
    case WJ21 = '21';
    case WJ22 = '22';
    case WJ24 = '24';
    case WJ265 = '26.5';
    case BI_HEX = 'Bi-Hex';
}
