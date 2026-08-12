<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Нормализует сырые значения ячеек Excel в пустые или типизированные значения для строк импорта.
 */
final readonly class ImportRowValueFormatter
{
    /**
     * Нормализовать значение ячейки Excel в строку или null.
     *
     * Шаги:
     * 1) Вернуть null для отсутствующего значения ячейки.
     * 2) Привести число или строку к строке и обрезать пробелы.
     * 3) Вернуть null для пустой строки после trim.
     */
    public function nullableString(string|int|float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }

    /**
     * Нормализовать значение ячейки Excel в целое число или null.
     *
     * Шаги:
     * 1) Обработать null/empty string как null.
     * 2) Принять целое число, вещественное число без дробной части или строку с цифрами.
     * 3) Выбросить исключение валидации для неподходящего значения.
     */
    public function nullableInt(string|int|float|null $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw ImportRowValidationException::fromMessage("Поле {$field}: ожидалось целое число.");
    }

    /**
     * Нормализовать значение ячейки Excel в вещественное число или null.
     *
     * Шаги:
     * 1) Обработать null/empty string как null.
     * 2) Принять числовое значение или строку с числом.
     * 3) Выбросить исключение валидации для неподходящего значения.
     */
    public function nullableFloat(string|int|float|null $value, string $field): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw ImportRowValidationException::fromMessage("Поле {$field}: ожидалось число.");
    }

    /**
     * Нормализовать значение ячейки Excel «Да/Нет» в булево значение или null.
     *
     * Шаги:
     * 1) Сначала нормализовать значение ячейки как строку или null.
     * 2) Сопоставить «Да» и «Нет» с булевыми значениями.
     * 3) Выбросить исключение валидации для другого непустого значения.
     */
    public function nullableBoolFromYesNo(string|int|float|null $value, string $field): ?bool
    {
        $value = $this->nullableString($value);

        return match ($value) {
            null => null,
            'Да' => true,
            'Нет' => false,
            default => throw ImportRowValidationException::fromMessage("Поле {$field}: ожидалось значение Да/Нет."),
        };
    }
}
