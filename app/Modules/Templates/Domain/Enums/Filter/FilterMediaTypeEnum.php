<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Filter;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/** Тип фильтрующего элемента (актуально для воздушных/салонных фильтров). */
enum FilterMediaTypeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case CARBON = 'Угольный';
    case DUST = 'Пылевой';
}
