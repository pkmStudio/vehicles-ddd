<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;
use App\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Templates\Domain\Enums\DetailTemplateEnum;

final readonly class VehicleWiperSheetRowMapper
{
    private const int SPEC_START_COLUMN = 20;

    public function __construct(
        private ImportRowValueFormatter $formatter,
        private DetailsDataFactoryInterface $detailsFactory,
    ) {}

    /**
     * Этот метод собирает DTO строки листа дворников из сырой Excel-строки.
     * Шаги:
     * 1) Читает слаг шаблона из ячейки 17; если он пуст — спецификации в строке нет, details
     *    остаётся пустым массивом.
     * 2) Если слаг есть — резолвит его в `DetailTemplateEnum` и просит `DetailsDataFactory`
     *    собрать details, начиная с колонки 20.
     * 3) Читает остальные поля строки (ms_id, имя особенности, name/text) и собирает DTO.
     *
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): VehicleWiperSheetRowDTO
    {
        $templateSlug = $this->formatter->nullableString($row[17] ?? null);
        $details = [];

        if ($templateSlug !== null) {
            $index = self::SPEC_START_COLUMN;
            $details = $this->detailsFactory->buildFromRow(DetailTemplateEnum::from($templateSlug), $row, $index)->toArray();
        }

        return new VehicleWiperSheetRowDTO(
            msId: $this->formatter->nullableInt($row[2] ?? null, 'ms_id'),
            templateSlug: $templateSlug,
            featureValueName: $this->formatter->nullableString($row[16] ?? null),
            name: $this->formatter->nullableString($row[18] ?? null),
            text: $this->formatter->nullableString($row[19] ?? null),
            details: $details,
        );
    }
}
