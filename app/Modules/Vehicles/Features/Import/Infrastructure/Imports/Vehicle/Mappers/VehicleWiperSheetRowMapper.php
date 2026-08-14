<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Formatters\ImportRowValueFormatter;

/**
 * Переводит строку листа дворников в DTO спецификации ТС.
 */
final readonly class VehicleWiperSheetRowMapper
{
    private const int MS_ID = 2;

    private const int FEATURE_VALUE_NAME = 16;

    private const int TEMPLATE_SLUG = 17;

    private const int NAME = 18;

    private const int TEXT = 19;

    private const int SPEC_START_COLUMN = 20;

    /**
     * Получить нормализатор ячеек и клиент сборки details.
     *
     * Шаги:
     * 1) Принять общий нормализатор строк импорта через DI.
     * 2) Принять клиент Templates для сборки типизированных details по слагу шаблона.
     */
    public function __construct(
        private ImportRowValueFormatter $formatter,
        private TemplatesClientInterface $templates,
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
        $templateSlug = $this->formatter->nullableString($row[self::TEMPLATE_SLUG] ?? null);
        $details = [];

        if ($templateSlug !== null) {
            $template = DetailTemplateEnum::tryFrom($templateSlug);
            if ($template === null) {
                throw ImportRowValidationException::fromMessage("Шаблон деталей «{$templateSlug}» не найден.");
            }

            $details = $this->templates->buildVehicleDetails(
                template: $template,
                row: $row,
                startIndex: self::SPEC_START_COLUMN,
            );
        }

        return new VehicleWiperSheetRowDTO(
            msId: $this->formatter->nullableInt($row[self::MS_ID] ?? null, 'ms_id'),
            templateSlug: $templateSlug,
            featureValueName: $this->formatter->nullableString($row[self::FEATURE_VALUE_NAME] ?? null),
            name: $this->formatter->nullableString($row[self::NAME] ?? null),
            text: $this->formatter->nullableString($row[self::TEXT] ?? null),
            details: $details,
        );
    }
}
