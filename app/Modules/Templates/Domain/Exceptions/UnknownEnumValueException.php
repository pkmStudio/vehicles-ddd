<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

/**
 * Ошибка неизвестного значения enum-справочника details при import/export.
 */
final class UnknownEnumValueException extends DetailsDataBuildException
{
    /**
     * Создает ошибку неизвестной подписи enum в human-readable словаре.
     */
    public static function label(string $dictionary, mixed $value): self
    {
        return new self(sprintf(
            'Не найдено совпадение в справочнике %s. Значение: %s',
            $dictionary,
            is_scalar($value) ? (string) $value : get_debug_type($value),
        ));
    }

    /**
     * Создает ошибку неизвестного stored enum name при export/render.
     */
    public static function name(string $dictionary, string $name): self
    {
        return new self(sprintf(
            'Не найдено имя в справочнике %s. Имя: %s',
            $dictionary,
            $name,
        ));
    }
}
