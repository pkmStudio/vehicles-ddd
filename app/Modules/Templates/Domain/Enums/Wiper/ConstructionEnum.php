<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Wiper;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/** Конструкция щётки стеклоочистителя. */
enum ConstructionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRAME = 'Каркасная';
    case FRAMELESS = 'Бескаркасная';
    case HYBRID = 'Гибридная';
}
