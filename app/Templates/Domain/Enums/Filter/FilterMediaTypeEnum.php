<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums\Filter;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/** Тип фильтрующего элемента (актуально для воздушных/салонных фильтров). */
enum FilterMediaTypeEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case CARBON = 'Угольный';
    case DUST = 'Пылевой';
}
