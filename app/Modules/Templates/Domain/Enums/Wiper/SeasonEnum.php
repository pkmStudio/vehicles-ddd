<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Wiper;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/** Сезонность щётки стеклоочистителя. */
enum SeasonEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case WINTER = 'Зима';
    case ALL_SEASON = 'На любой сезон, Демисезон';
}
