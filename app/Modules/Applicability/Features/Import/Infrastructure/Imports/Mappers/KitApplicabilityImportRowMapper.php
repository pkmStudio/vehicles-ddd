<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Imports\Mappers;

use App\Modules\Applicability\Features\Import\Domain\DTOs\KitApplicabilityImportRowDTO;
use App\Modules\Applicability\Features\Import\Domain\Exceptions\ImportRowValidationException;

/**
 * Переводит Excel-строку применяемости в typed row DTO.
 */
final readonly class KitApplicabilityImportRowMapper
{
    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): KitApplicabilityImportRowDTO
    {
        return new KitApplicabilityImportRowDTO(
            msId: $this->positiveInt($row[0] ?? null, 'ms_id'),
            modId: $this->positiveInt($row[1] ?? null, 'mod_id'),
            kitId: $this->positiveInt($row[2] ?? null, 'kit_id'),
        );
    }

    private function positiveInt(string|int|float|null $value, string $field): int
    {
        if ($value === null) {
            throw new ImportRowValidationException("Поле {$field} обязательно для заполнения.");
        }

        $value = is_string($value) ? trim($value) : (string) $value;

        if ($value === '' || preg_match('/^\d+$/', $value) !== 1 || (int) $value <= 0) {
            throw new ImportRowValidationException("Поле {$field} должно быть положительным целым числом.");
        }

        return (int) $value;
    }
}
