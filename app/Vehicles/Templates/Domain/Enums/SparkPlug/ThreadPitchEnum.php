<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Enums\SparkPlug;

use App\Vehicles\Templates\Domain\Traits\EnumHelperTrait;
use App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface;

/** Шаг резьбы свечи зажигания (мм). */
enum ThreadPitchEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case TP1 = '1';
    case TP125 = '1.25';
    case TP15 = '1.5';
}
