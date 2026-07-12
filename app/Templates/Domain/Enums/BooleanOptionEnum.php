<?php

declare(strict_types=1);

namespace App\Templates\Domain\Enums;

use App\Templates\Domain\Traits\EnumHelperTrait;
use App\Templates\Domain\Contracts\EnumHelperInterface;

/**
 * Булево поле, представленное как select в Excel-форме (лейблы "Да"/"Нет"), а не как настоящий
 * JSON-boolean. Используется только внутри Factory/Presenter для парсинга/рендера лейбла — в самих
 * `Data`-классах булевы поля хранятся как обычный `bool`, этот enum наружу не течёт.
 */
enum BooleanOptionEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case TRUE = 'Да';
    case FALSE = 'Нет';
}
