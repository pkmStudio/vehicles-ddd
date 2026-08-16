<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\EngineModification\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку TecDoc Excel в командный DTO связи двигателя и модификации.
 */
final readonly class EngineModificationTdRowMapper
{
    private const int ENG_ID = 0;

    private const int MOD_ID = 1;

    private const int TYPE = 2;

    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при строгом разборе обязательных колонок связи.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Преобразовать строку Excel в DTO связи двигателя и модификации.
     *
     * Шаги:
     * 1) Прочитать eng_id, mod_id и type из фиксированных колонок.
     * 2) Потребовать все значения как обязательные поля TecDoc-связи.
     * 3) Вернуть типизированный DTO командной строки.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): EngineModificationRowDTO
    {
        return new EngineModificationRowDTO(
            engId: $this->formatter->requiredInt($row[self::ENG_ID] ?? null, 'eng_id'),
            modId: $this->formatter->requiredInt($row[self::MOD_ID] ?? null, 'mod_id'),
            type: $this->formatter->requiredString($row[self::TYPE] ?? null, 'type'),
        );
    }
}
