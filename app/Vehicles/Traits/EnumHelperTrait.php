<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

trait EnumHelperTrait
{
    /**
     * Метод для получения всех имен
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Метод для получения всех значений
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Метод перевода из Enum в Array
     */
    public static function toArray(): array
    {
        return array_combine(self::names(), self::values());
    }
}
