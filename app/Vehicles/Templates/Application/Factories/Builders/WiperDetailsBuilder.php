<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application\Factories\Builders;

use App\Vehicles\Templates\Application\Factories\DetailsRowCursor;
use App\Vehicles\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Vehicles\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperBackDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperFrontDetailsData;
use App\Vehicles\Templates\Domain\ModelData\Vehicle\WiperLengthRangeData;

/**
 * Строит форму шаблона `wiper` (обе стороны) из Excel-строки. Выделено из `DetailsDataFactory`,
 * чтобы логика дворников не лежала в одном файле с другими шаблонами. Простой класс без
 * собственного порта — вызывается только из `DetailsDataFactory`, подмена не нужна.
 */
final readonly class WiperDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): WiperDetailsData
    {
        return new WiperDetailsData(
            front: $this->buildFront($cursor),
            back: $this->buildBack($cursor),
        );
    }

    /**
     * Читает 6 ячеек подряд: диапазон length_main, диапазон length_second, тип крепления,
     * количество щёток.
     */
    private function buildFront(DetailsRowCursor $cursor): WiperFrontDetailsData
    {
        return new WiperFrontDetailsData(
            lengthMain: $this->buildLengthRange($cursor),
            lengthSecond: $this->buildLengthRange($cursor),
            adapterTypeFront: $this->namesOf($cursor->pullMultiLabel(FrontAdapterTypeEnum::class)),
            countWipers: $cursor->pullIntCell(),
        );
    }

    /**
     * Читает 4 ячейки подряд: диапазон length_rear, тип крепления, количество щёток.
     */
    private function buildBack(DetailsRowCursor $cursor): WiperBackDetailsData
    {
        return new WiperBackDetailsData(
            lengthRear: $this->buildLengthRange($cursor),
            adapterTypeRear: $this->namesOf($cursor->pullMultiLabel(RearAdapterTypeEnum::class)),
            countWipers: $cursor->pullIntCell(),
        );
    }

    private function buildLengthRange(DetailsRowCursor $cursor): WiperLengthRangeData
    {
        return new WiperLengthRangeData(
            min: $cursor->pullIntCell(),
            max: $cursor->pullIntCell(),
        );
    }

    /**
     * Превращает массив резолвнутых case'ов в массив их хранимых имён (`->name`) — то, что
     * реально кладётся в поле `Data`-класса и, дальше, в details JSON.
     *
     * @param  array<int, \App\Vehicles\Templates\Domain\Contracts\EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        return array_map(static fn ($case) => $case->name, $cases);
    }
}
