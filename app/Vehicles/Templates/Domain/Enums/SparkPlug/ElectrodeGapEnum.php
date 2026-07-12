<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums\SparkPlug;

use App\Vehicles\Templates\Domain\Traits\EnumHelperTrait;
use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;

/** Межконтактный зазор свечи зажигания (мм). */
enum ElectrodeGapEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case G06 = '0.6';
    case G065 = '0.65';
    case G07 = '0.7';
    case G075 = '0.75';
    case G08 = '0.8';
    case G085 = '0.85';
    case G09 = '0.9';
    case G1 = '1';
    case G11 = '1.1';
    case G13 = '1.3';
    case G15 = '1.5';
}
