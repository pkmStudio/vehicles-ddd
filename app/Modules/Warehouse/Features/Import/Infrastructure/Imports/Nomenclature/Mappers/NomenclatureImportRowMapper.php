<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\Nomenclature\NomenclatureImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Переводит Excel-строку номенклатуры в typed row DTO.
 */
final readonly class NomenclatureImportRowMapper
{
    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): NomenclatureImportRowDTO
    {
        $id = $this->nullableInt($row[0] ?? null, 'ID');
        $typeName = $this->requiredString($row[1] ?? null, 'Тип товара');
        $brandName = $this->requiredString($row[2] ?? null, 'Бренд');
        $name = $this->requiredString($row[3] ?? null, 'Название');
        $country = $this->requiredString($row[4] ?? null, 'Страна');
        $partNumber = $this->requiredString($row[5] ?? null, 'Артикул');
        $color = $this->requiredString($row[6] ?? null, 'Цвет');
        $weight = $this->positiveInt($row[7] ?? null, 'Вес');
        $quantityPak = $this->positiveInt($row[10] ?? null, 'Кол-во упаковок');
        $quantityInPak = $this->positiveInt($row[11] ?? null, 'Кол-во шт в упаковке');

        return new NomenclatureImportRowDTO(
            id: $id,
            typeName: $typeName,
            brandName: $brandName,
            name: $name,
            country: $country,
            partNumber: $partNumber,
            color: $color,
            weight: $weight,
            materialLabels: $this->nullableString($row[8] ?? null) ?? '',
            vehicleTypeLabels: $this->nullableString($row[9] ?? null) ?? '',
            quantityPak: $quantityPak,
            quantityInPak: $quantityInPak,
            sourceCells: $row,
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
            throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть положительным целым числом.");
        }

        return $value;
    }
}
