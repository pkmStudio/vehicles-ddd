<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\SparkPlug;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/** Шаг резьбы свечи зажигания (мм). */
enum ThreadPitchEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case TP1 = '1';
    case TP125 = '1.25';
    case TP15 = '1.5';
}
