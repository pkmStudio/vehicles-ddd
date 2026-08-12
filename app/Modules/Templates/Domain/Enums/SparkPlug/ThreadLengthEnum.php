<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\SparkPlug;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/** Длина резьбы свечи зажигания (мм). */
enum ThreadLengthEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case TL127 = '12.7';
    case TL175 = '17.5';
    case TL19 = '19';
    case TL215 = '21.5';
    case TL25 = '25';
    case TL265 = '26.5';
    case TL28 = '28';
    case TL285 = '28.5';
    case TL295 = '29.5';
}
