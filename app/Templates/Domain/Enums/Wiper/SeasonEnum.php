<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\Wiper;

use App\Templates\Domain\Contracts\EnumHelperInterface;
use App\Templates\Domain\Traits\EnumHelperTrait;

/** Сезонность щётки стеклоочистителя. */
enum SeasonEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case WINTER = 'Зима';
    case ALL_SEASON = 'На любой сезон, Демисезон';
}
