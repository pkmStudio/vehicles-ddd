<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Enums\Wiper;

/**
 * Сторона дворника — корневой JSON-ключ структуры details (`front`/`back`).
 */
enum WiperSideEnum: string
{
    case FRONT = 'front';
    case BACK = 'back';

    /**
     * Возвращает поле адаптера внутри details конкретной стороны.
     * Front использует `adapter_type_front`, back — `adapter_type_rear`.
     */
    public function adapterField(): string
    {
        return match ($this) {
            self::FRONT => 'adapter_type_front',
            self::BACK => 'adapter_type_rear',
        };
    }
}
