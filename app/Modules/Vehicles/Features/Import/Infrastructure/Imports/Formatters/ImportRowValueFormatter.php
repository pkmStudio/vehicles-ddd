<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters;

use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;

final readonly class ImportRowValueFormatter
{
    public function nullableString(string|int|float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }

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
