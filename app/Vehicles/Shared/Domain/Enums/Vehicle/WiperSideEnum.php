<?php

declare(strict_types=1);

namespace App\Vehicles\Shared\Domain\Enums\Vehicle;

/**
 * Сторона дворника — корневой JSON-ключ структуры деталей PartSpecification (`front`/`back`).
 * Значения — это ключи данных (по ним идёт jsonb_exists / split / merge), НЕ лейблы для UI.
 * Доменное понятие: используется и шаблоном (Domain), и сервисом разбора (Application).
 */
enum WiperSideEnum: string
{
    case FRONT = 'front';
    case BACK = 'back';

    /** Поле адаптера в деталях стороны. */
    public function adapterField(): string
    {
        return match ($this) {
            self::FRONT => 'adapter_type_front',
            self::BACK => 'adapter_type_rear',
        };
    }
}
