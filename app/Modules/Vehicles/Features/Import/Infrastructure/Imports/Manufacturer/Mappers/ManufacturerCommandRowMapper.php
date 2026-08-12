<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку командного импорта производителей в DTO.
 */
final readonly class ManufacturerCommandRowMapper
{
    /**
     * Получить нормализатор значений ячеек Excel.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Использовать его при чтении идентификатора и имени производителя.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Собрать DTO производителя из строки командного импорта.
     *
     * Шаги:
     * 1) Прочитать mfa_id из первой колонки и имя из второй.
     * 2) Нормализовать идентификатор как целое число, а имя как строку.
     * 3) Вернуть DTO для построчного сервиса сохранения производителя.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): ManufacturerCommandRowDTO
    {
        return new ManufacturerCommandRowDTO(
            mfaId: $this->formatter->nullableInt($row[0] ?? null, 'mfa_id'),
            name: $this->formatter->nullableString($row[1] ?? null),
        );
    }
}
