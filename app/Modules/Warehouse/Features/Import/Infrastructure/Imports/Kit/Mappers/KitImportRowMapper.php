<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Kit\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\Kit\KitImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Переводит Excel-строку набора в typed row DTO.
 */
final readonly class KitImportRowMapper
{
    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): KitImportRowDTO
    {
        $partNumbers = $this->partNumbers($row[1] ?? null);

        if ($partNumbers === []) {
            throw ImportRowValidationException::withMessage('Список артикулов набора пуст');
        }

        return new KitImportRowDTO(
            id: $this->nullableInt($row[0] ?? null, 'ID комплекта'),
            partNumbers: $partNumbers,
            isSaleSeparately: $this->yesNo($row[2] ?? null, 'Может продаваться отдельно'),
            isActive: $this->yesNo($row[3] ?? null, 'Активен'),
        );
    }

    /**
     * @return array<int, string>
     */
    private function partNumbers(string|int|float|null $value): array
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(';', $value))));
    }

    private function yesNo(string|int|float|null $value, string $field): bool
    {
        return match ($this->nullableString($value)) {
            'Да' => true,
            'Нет' => false,
            default => throw ImportRowValidationException::withMessage("Поле «{$field}» должно быть Да или Нет."),
        };
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

    private function nullableString(string|int|float|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        return $value === '' ? null : $value;
    }
}
