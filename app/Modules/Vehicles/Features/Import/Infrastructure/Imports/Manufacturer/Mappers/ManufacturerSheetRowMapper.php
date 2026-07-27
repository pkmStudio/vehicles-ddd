<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Manufacturer\Mappers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class ManufacturerSheetRowMapper
{
    public function __construct(
        private ImportRowValueFormatter $formatter,
    ) {}

    /**
     * Все три колонки обязательны — пустая/отсутствующая колонка бракует строку сразу здесь,
     * не доходя до сервиса/фабрики.
     *
     * @param  array<int, string|int|float|null>  $row
     *
     * @throws ImportRowValidationException
     */
    public function map(array $row): ManufacturerSheetRowDTO
    {
        $mfaId = $this->formatter->nullableInt($row[0] ?? null, 'mfa_id');
        $name = $this->formatter->nullableString($row[1] ?? null);
        $provider = $this->formatter->nullableString($row[2] ?? null);

        $errors = [];
        if ($mfaId === null) {
            $errors['mfa_id'] = ['Поле mfa_id обязательно.'];
        }
        if ($name === null) {
            $errors['name'] = ['Поле name обязательно.'];
        }
        if ($provider === null) {
            $errors['provider'] = ['Поле provider обязательно.'];
        }

        if ($errors !== []) {
            throw ImportRowValidationException::fromMessages($errors);
        }

        return new ManufacturerSheetRowDTO(
            mfaId: $mfaId,
            name: $name,
            provider: $provider,
        );
    }
}
