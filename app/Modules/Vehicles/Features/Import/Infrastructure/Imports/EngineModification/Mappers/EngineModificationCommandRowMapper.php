<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку Excel в командный DTO связи двигателя и модификации.
 */
final readonly class EngineModificationCommandRowMapper
{
    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при разборе nullable-значений колонок связи.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Преобразовать строку Excel в DTO связи двигателя и модификации.
     *
     * Шаги:
     * 1) Прочитать eng_id, mod_id и type из фиксированных колонок.
     * 2) Нормализовать значения через общий нормализатор строк импорта.
     * 3) Вернуть типизированный DTO командной строки.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineModificationCommandRowDTO
    {
        return new EngineModificationCommandRowDTO(
            engId: $this->formatter->nullableInt($row[0] ?? null, 'eng_id'),
            modId: $this->formatter->nullableInt($row[1] ?? null, 'mod_id'),
            type: $this->formatter->nullableString($row[2] ?? null),
        );
    }
}
