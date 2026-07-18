<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Enums\Vehicle;

enum GearTypeEnum: string
{
    case AUTOMATIC = 'Автоматическая коробка передач';
    case MANUAL = 'Механическая коробка передач';
    case MANUAL_AUTO = 'Механическая коробка передач, автоматическое управление';
    case CVT = 'Вариатор (бесступенчатая коробка передач)';
    case OVERDRIVE = 'Коробка передач с ускоряющей передачей';
}
