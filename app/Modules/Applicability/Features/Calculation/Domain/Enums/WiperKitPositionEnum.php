<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Enums;

enum WiperKitPositionEnum: string
{
    case FRONT = 'front';
    case BACK = 'back';
    case UNIVERSAL = 'universal';

    /**
     * Нормализует сохраненное значение позиции дворников.
     */
    public static function fromStoredValue(?string $value): ?self
    {
        return match ($value) {
            'FRONT', 'Переднее', 'front' => self::FRONT,
            'BACK', 'Заднее', 'back' => self::BACK,
            'UNIVERSAL', 'Универсальное', 'universal' => self::UNIVERSAL,
            default => null,
        };
    }
}
