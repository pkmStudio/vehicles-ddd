<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

/**
 * Ошибка значения ячейки details, когда поле заполнено, но не соответствует ожидаемому типу.
 */
final class InvalidDetailsCellException extends DetailsDataBuildException
{
    /**
     * Создает ошибку для ячейки, которая должна содержать число.
     */
    public static function numeric(string $field, string|int|float|null $value): self
    {
        return new self(sprintf(
            'Поле «%s» должно быть числом. Значение: %s',
            $field,
            $value === null ? 'null' : (string) $value,
        ));
    }
}
