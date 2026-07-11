<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Vehicles\Import\Domain\Contracts\Services\Template\TemplateDataBuilderInterface;
use App\Vehicles\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

final readonly class VehicleWiperSheetRowMapper
{
    private const int SPEC_START_COLUMN = 20;

    public function __construct(
        private ImportRowValueFormatter $formatter,
        private TemplateDataBuilderInterface $templateDataBuilder,
    ) {}

    /**
     * @param  array<int, string|int|float|null>  $row
     */
    public function map(array $row): VehicleWiperSheetRowDTO
    {
        $templateSlug = $this->formatter->nullableString($row[17] ?? null);
        $details = [];

        if ($templateSlug !== null) {
            $details = $this->templateDataBuilder->buildBySlug($row, self::SPEC_START_COLUMN, $templateSlug);
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
