<?php

declare(strict_types=1);

namespace Dan\FieldTemplates\Attributes\SparkPlug;

use Dan\FieldTemplates\Support\EnumHelperTrait;

enum ThreadPitchEnum: string
{
    use EnumHelperTrait;

    case TP1 = '1';
    case TP125 = '1.25';
    case TP15 = '1.5';
}
