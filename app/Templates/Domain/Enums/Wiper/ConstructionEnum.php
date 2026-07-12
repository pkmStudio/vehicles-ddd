<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\Wiper;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Конструкция щётки стеклоочистителя. */
enum ConstructionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRAME = 'Каркасная';
    case FRAMELESS = 'Бескаркасная';
    case HYBRID = 'Гибридная';
}
