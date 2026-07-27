<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

use DomainException;

/**
 * Ошибка сборки typed details из импортной строки или другого внешнего представления.
 */
final class DetailsDataBuildException extends DomainException
{
    public static function requiredField(string $field): self
    {
        return new self("Поле «{$field}» обязательно для заполнения.");
    }

    public static function unknownDictionaryValue(string $dictionary, mixed $value): self
    {
        return new self(sprintf(
            'Не найдено совпадение в справочнике %s. Значение: %s',
            $dictionary,
            $value,
        ));
    }
}
