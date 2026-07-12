<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\Wiper;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Сезонность щётки стеклоочистителя. */
enum SeasonEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case WINTER = 'Зимняя';
    case ALL_SEASON = 'Всесезонная';
}
