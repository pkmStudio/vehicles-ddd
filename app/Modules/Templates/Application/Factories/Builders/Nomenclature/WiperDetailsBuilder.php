<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\ParsesBooleanCells;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;

/**
 * Строит форму шаблона `wiper` (Nomenclature) из Excel-строки — характеристики самого
 * товара-щётки. Не подключена ни к одному Import/Export сценарию — см. докблок
 * `WiperDetailsData`. Простой класс без собственного порта — вызывается только из
 * `NomenclatureDetailsDataFactory`.
 */
final readonly class WiperDetailsBuilder
{
    use ParsesBooleanCells;

    public function build(DetailsRowCursor $cursor): WiperDetailsData
    {
        return new WiperDetailsData(
            position: $cursor->pullRequiredLabel(PositionEnum::class, 'Расположение')->name,
            category: $cursor->pullRequiredLabel(CategoryEnum::class, 'Категория')->name,
            construction: $cursor->pullRequiredLabel(ConstructionEnum::class, 'Конструкция')->name,
            season: $cursor->pullRequiredLabel(SeasonEnum::class, 'Сезон')->name,
            lengthMain: $cursor->pullRequiredIntCell('Длина водительской'),
            lengthSecond: $cursor->pullRequiredIntCell('Длина пассажирской'),
            lengthRear: $cursor->pullRequiredIntCell('Длина задней'),
            adapterTypeFront: $this->namesOf($cursor->pullRequiredMultiLabel(FrontAdapterTypeEnum::class, 'Тип крепления передних')),
            adapterTypeRear: $this->namesOf($cursor->pullRequiredMultiLabel(RearAdapterTypeEnum::class, 'Тип крепления задней')),
            coating: $cursor->pullRequiredStringCell('Покрытие'),
            wearSensor: $this->pullRequiredBoolLabel($cursor, 'Датчик износа'),
            spoiler: $this->pullRequiredBoolLabel($cursor, 'Спойлер'),
            washerNozzle: $this->pullRequiredBoolLabel($cursor, 'Форсунка омывателя'),
            heated: $this->pullRequiredBoolLabel($cursor, 'C подогревом'),
            steering: $cursor->pullRequiredLabel(SteeringCompatibilityEnum::class, 'Рулевое управление')->name,
        );
    }

    /**
     * @param  array<int, \App\Modules\Templates\Domain\Contracts\EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        $toName = static fn ($case) => $case->name;

        return array_map($toName, $cases);
    }
}
