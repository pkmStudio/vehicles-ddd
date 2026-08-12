<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Kit\Mappers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\Kit\KitImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит Excel-строку набора в typed row DTO.
 */
final readonly class KitImportRowMapper
{
    private ImportRowValueFormatter $formatter;

    public function __construct(?ImportRowValueFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new ImportRowValueFormatter;
    }

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): KitImportRowDTO
    {
        $partNumbers = $this->formatter->semicolonStringList($row[1] ?? null);

        if ($partNumbers === []) {
            throw ImportRowValidationException::withMessage('Список артикулов набора пуст');
        }

        return new KitImportRowDTO(
            id: $this->formatter->nullableInt($row[0] ?? null, 'ID комплекта'),
            partNumbers: $partNumbers,
            isSaleSeparately: $this->formatter->yesNo($row[2] ?? null, 'Может продаваться отдельно'),
            isActive: $this->formatter->yesNo($row[3] ?? null, 'Активен'),
        );
    }
}
