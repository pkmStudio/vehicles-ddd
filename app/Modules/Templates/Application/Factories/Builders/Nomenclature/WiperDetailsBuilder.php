<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\ParsesBooleanCells;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\CategoryEnum;
use App\Modules\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Modules\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
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
        $position = $cursor->pullRequiredLabel(PositionEnum::class, 'Расположение')->name;
        $category = $cursor->pullRequiredLabel(CategoryEnum::class, 'Категория')->name;
        $construction = $cursor->pullRequiredLabel(ConstructionEnum::class, 'Конструкция')->name;
        $season = $cursor->pullRequiredLabel(SeasonEnum::class, 'Сезон')->name;
        $lengthMain = $cursor->pullIntCell();
        $lengthSecond = $cursor->pullIntCell();
        $lengthRear = $cursor->pullIntCell();

        if ($lengthMain === null && $lengthRear === null) {
            throw DetailsDataBuildException::requiredField('Длина водительской или длина задней');
        }

        return new WiperDetailsData(
            position: $position,
            category: $category,
            construction: $construction,
            season: $season,
            lengthMain: $lengthMain,
            lengthSecond: $lengthSecond,
            lengthRear: $lengthRear,
            adapterTypeFront: $this->namesOf($cursor->pullMultiLabel(FrontAdapterTypeEnum::class)),
            adapterTypeRear: $this->namesOf($cursor->pullMultiLabel(RearAdapterTypeEnum::class)),
            coating: $cursor->pullRequiredStringCell('Покрытие'),
            wearSensor: $this->pullRequiredBoolLabel($cursor, 'Датчик износа'),
            spoiler: $this->pullRequiredBoolLabel($cursor, 'Спойлер'),
            washerNozzle: $this->pullRequiredBoolLabel($cursor, 'Форсунка омывателя'),
            heated: $this->pullRequiredBoolLabel($cursor, 'C подогревом'),
            steering: $cursor->pullRequiredLabel(SteeringCompatibilityEnum::class, 'Рулевое управление')->name,
        );
    }

    /**
     * @param  array<int, EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        $toName = static fn ($case) => $case->name;

        return array_map($toName, $cases);
    }
}
