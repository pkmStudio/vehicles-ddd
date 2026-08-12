<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Formatters;

use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Нормализует сырые значения ячеек Excel для Warehouse import mapper-ов.
 */
final readonly class ImportRowValueFormatter
{
    public function requiredString(string|int|float|null $value, string $field): string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» обязательно для заполнения.");
        }

        return $value;
    }

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
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d+$/', $value) !== 1) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть целым числом.");
        }

        return (int) $value;
    }

    public function positiveInt(string|int|float|null $value, string $field): int
    {
        $value = $this->nullableInt($value, $field);

        if ($value === null || $value <= 0) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть положительным целым числом.");
        }

        return $value;
    }

    public function nonNegativeInt(string|int|float|null $value, string $field): int
    {
        $value = $this->nullableInt($value, $field);

        if ($value === null || $value < 0) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть нулем или положительным числом.");
        }

        return $value;
    }

    public function yesNo(string|int|float|null $value, string $field): bool
    {
        return match ($this->nullableString($value)) {
            'Да' => true,
            'Нет' => false,
            default => throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть Да или Нет."),
        };
    }

    /**
     * @return array<int, string>
     */
    public function semicolonStringList(string|int|float|null $value): array
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(';', $value))));
    }
}
