<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\SparkPlug;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum ThreadSizeEnum: string
{
    use EnumHelperTrait;

    case M10X1 = 'M10x1'; // (мотоциклы, бензопилы, газонокосилки)
    case M12X125 = 'M12x1.25'; // (мотоциклы)
    case M14X125 = 'M14x1.25'; // (автомобили)
    case M18X15 = 'M18x1.5'; // (автомобили)
}
