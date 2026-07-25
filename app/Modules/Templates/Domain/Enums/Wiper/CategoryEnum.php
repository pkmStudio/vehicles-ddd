<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Wiper;

use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Traits\EnumHelperTrait;

/** Ручная категория щётки стеклоочистителя. */
enum CategoryEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case FRAMELESS = 'Бескаркасные';
    case WINTER = 'Зимние';
    case HYBRID = 'Гибридные';
    case FRAME = 'Каркасные';
    case REAR = 'Задние';
}
