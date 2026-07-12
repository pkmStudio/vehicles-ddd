<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\ParsesBooleanCells;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Templates\Domain\Enums\Wiper\SeasonEnum;
use App\Templates\Domain\Enums\Wiper\SteeringCompatibilityEnum;
use App\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;

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
            position: $cursor->pullLabel(PositionEnum::class)?->name,
            construction: $cursor->pullLabel(ConstructionEnum::class)?->name,
            season: $cursor->pullLabel(SeasonEnum::class)?->name,
            lengthMain: $cursor->pullIntCell(),
            lengthSecond: $cursor->pullIntCell(),
            lengthRear: $cursor->pullIntCell(),
            adapterTypeFront: $this->namesOf($cursor->pullMultiLabel(FrontAdapterTypeEnum::class)),
            adapterTypeRear: $this->namesOf($cursor->pullMultiLabel(RearAdapterTypeEnum::class)),
            coating: $cursor->pullStringCell(),
            wearSensor: $this->pullBoolLabel($cursor),
            spoiler: $this->pullBoolLabel($cursor),
            washerNozzle: $this->pullBoolLabel($cursor),
            heated: $this->pullBoolLabel($cursor),
            steering: $cursor->pullLabel(SteeringCompatibilityEnum::class)?->name,
        );
    }

    /**
     * @param  array<int, \App\Templates\Domain\Contracts\EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        return array_map(static fn ($case) => $case->name, $cases);
    }
}
