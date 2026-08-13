<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

use DomainException;

/**
 * Ошибка сборки typed details из импортной строки или другого внешнего представления.
 */
class DetailsDataBuildException extends DomainException
{
    /**
     * Создает ошибку обязательного поля details-шаблона.
     */
    public static function requiredField(string $field): self
    {
        return new self("Поле «{$field}» обязательно для заполнения.");
    }

    /**
     * Создает ошибку неизвестного значения справочника details-шаблона.
     */
    public static function unknownDictionaryValue(string $dictionary, string|int|float|null $value): self
    {
        return UnknownEnumValueException::label($dictionary, $value);
    }
}
