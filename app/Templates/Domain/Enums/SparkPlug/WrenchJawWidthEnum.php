<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\SparkPlug;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Ширина зева гаечного ключа под свечу (мм). */
enum WrenchJawWidthEnum: string implements EnumHelperInterface
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
