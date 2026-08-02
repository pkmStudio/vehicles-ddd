<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\Contracts\EnumHelperInterface;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperBackDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperFrontDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperLengthRangeData;

/**
 * Строит форму шаблона `wiper` (обе стороны) из Excel-строки. Выделено из `DetailsDataFactory`,
 * чтобы логика дворников не лежала в одном файле с другими шаблонами. Простой класс без
 * собственного порта — вызывается только из `DetailsDataFactory`, подмена не нужна.
 */
final readonly class WiperDetailsBuilder
{
    public function build(DetailsRowCursor $cursor): WiperDetailsData
    {
        $front = $this->buildOptionalFront($cursor);
        $back = $this->buildOptionalBack($cursor);

        if ($front === null && $back === null) {
            throw DetailsDataBuildException::requiredField('Минимальная длина щётки');
        }

        return new WiperDetailsData(
            front: $front,
            back: $back,
        );
    }

    private function buildOptionalFront(DetailsRowCursor $cursor): ?WiperFrontDetailsData
    {
        $cells = $this->pullCells($cursor, 6);

        if ($this->allBlank($cells)) {
            return null;
        }

        return $this->buildFront(new DetailsRowCursor($cells));
    }

    private function buildOptionalBack(DetailsRowCursor $cursor): ?WiperBackDetailsData
    {
        $cells = $this->pullCells($cursor, 4);

        if ($this->allBlank($cells)) {
            return null;
        }

        return $this->buildBack(new DetailsRowCursor($cells));
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
            adapterTypeFront: $this->namesOf($cursor->pullRequiredMultiLabel(FrontAdapterTypeEnum::class, 'Тип крепления передних')),
            countWipers: $cursor->pullRequiredIntCell('Количество передних щёток'),
        );
    }

    /**
     * Читает 4 ячейки подряд: диапазон length_rear, тип крепления, количество щёток.
     */
    private function buildBack(DetailsRowCursor $cursor): WiperBackDetailsData
    {
        return new WiperBackDetailsData(
            lengthRear: $this->buildLengthRange($cursor),
            adapterTypeRear: $this->namesOf($cursor->pullRequiredMultiLabel(RearAdapterTypeEnum::class, 'Тип крепления задней')),
            countWipers: $cursor->pullRequiredIntCell('Количество задних щёток'),
        );
    }

    private function buildLengthRange(DetailsRowCursor $cursor): WiperLengthRangeData
    {
        return new WiperLengthRangeData(
            min: $cursor->pullRequiredIntCell('Минимальная длина щётки'),
            max: $cursor->pullRequiredIntCell('Максимальная длина щётки'),
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function pullCells(DetailsRowCursor $cursor, int $count): array
    {
        $cells = [];

        for ($i = 0; $i < $count; $i++) {
            $cells[] = $cursor->pullCell();
        }

        return $cells;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function allBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== null && $cell !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Превращает массив резолвнутых case'ов в массив их хранимых имён (`->name`) — то, что
     * реально кладётся в поле `Data`-класса и, дальше, в details JSON.
     *
     * @param  array<int, EnumHelperInterface>  $cases
     * @return array<int, string>
     */
    private function namesOf(array $cases): array
    {
        $toName = static fn ($case) => $case->name;

        return array_map($toName, $cases);
    }
}
