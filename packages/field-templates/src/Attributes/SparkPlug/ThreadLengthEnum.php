<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\SparkPlug;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum ThreadLengthEnum: string
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
