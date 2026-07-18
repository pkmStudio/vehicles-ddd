<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Wiper;

use App\Modules\Templates\Domain\Traits\EnumHelperTrait;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;

/**
 * Сторона руля, под которую подходит щётка. НЕ то же самое, что
 * `App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum` (атрибут самого ТС) — здесь это
 * характеристика товара (совместимость), сознательно отдельный enum, чтобы не тянуть Warehouse-код
 * на внутренности Vehicles ради трёх похожих по смыслу значений.
 */
enum SteeringCompatibilityEnum: string implements EnumHelperInterface
{
    use EnumHelperTrait;

    case LEFT = 'Левый руль';
    case RIGHT = 'Правый руль';
    case BOTH = 'Левый + Правый';
}
