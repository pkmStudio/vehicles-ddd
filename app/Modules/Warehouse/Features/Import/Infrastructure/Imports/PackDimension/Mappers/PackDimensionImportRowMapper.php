<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\PackDimension\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\PackDimension\PackDimensionImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Переводит Excel-строку упаковки в typed row DTO.
 */
final readonly class PackDimensionImportRowMapper
{
    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): PackDimensionImportRowDTO
    {
        return new PackDimensionImportRowDTO(
            id: $this->nullableInt($row[0] ?? null, 'ID'),
            name: $this->requiredString($row[1] ?? null, 'Название коробки'),
            weight: $this->positiveInt($row[2] ?? null, 'Вес'),
            width: $this->positiveInt($row[3] ?? null, 'Ширина'),
            height: $this->positiveInt($row[4] ?? null, 'Высота'),
            length: $this->positiveInt($row[5] ?? null, 'Длина'),
            price: $this->nonNegativeInt($row[6] ?? null, 'Цена'),
            type: $this->requiredString($row[7] ?? null, 'Тип товара'),
        );
    }

    private function requiredString(string|int|float|null $value, string $field): string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» обязательно для заполнения.");
        }

        return $value;
    }

    private function nullableString(string|int|float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }

    private function nullableInt(string|int|float|null $value, string $field): ?int
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

    private function positiveInt(string|int|float|null $value, string $field): int
    {
        $value = $this->nullableInt($value, $field);

        if ($value === null || $value <= 0) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть больше нуля.");
        }

        return $value;
    }

    private function nonNegativeInt(string|int|float|null $value, string $field): int
    {
        $value = $this->nullableInt($value, $field);

        if ($value === null || $value < 0) {
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть нулем или положительным числом.");
        }

        return $value;
    }
}
