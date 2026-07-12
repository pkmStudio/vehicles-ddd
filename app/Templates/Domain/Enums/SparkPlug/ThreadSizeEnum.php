<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\SparkPlug;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Размер резьбы свечи зажигания. */
enum ThreadSizeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case M10X1 = 'M10x1'; // (мотоциклы, бензопилы, газонокосилки)
    case M12X125 = 'M12x1.25'; // (мотоциклы)
    case M14X125 = 'M14x1.25'; // (автомобили)
    case M18X15 = 'M18x1.5'; // (автомобили)
}
