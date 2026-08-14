<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Маппит строку manager Excel-листа связей в DTO.
 */
final readonly class EngineModificationSheetRowMapper
{
    private const int MOD_ID = 0;

    private const int ENG_ID = 1;

    private const int TYPE = 2;

    /**
     * Получить нормализатор Excel-ячеек.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO связи из фиксированных колонок.
     *
     * Шаги:
     * 1) Прочитать mod_id, eng_id и type.
     * 2) Нормализовать обязательные значения через formatter.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineModificationRowDTO
    {
        return new EngineModificationRowDTO(
            modId: $this->formatter->requiredInt($row[self::MOD_ID] ?? null, 'mod_id'),
            engId: $this->formatter->requiredInt($row[self::ENG_ID] ?? null, 'eng_id'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'type'),
        );
    }
}
