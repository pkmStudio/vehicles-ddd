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
    /**
     * Этот метод собирает vehicle wiper details из переднего и заднего блоков Excel-строки.
     * Шаги:
     * 1) Пытается прочитать передний блок дворников как optional-набор из 6 ячеек.
     * 2) Пытается прочитать задний блок как optional-набор из 4 ячеек.
     * 3) Если обе стороны пустые — бросает ошибку обязательной минимальной длины.
     * 4) Возвращает `WiperDetailsData` с заполненной хотя бы одной стороной.
     */
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

    /**
     * Этот метод читает optional-передний блок дворников, не валидируя пустой набор как ошибку.
     * Шаги:
     * 1) Забирает из основного курсора ровно 6 ячеек переднего блока.
     * 2) Если все они пустые — возвращает null и оставляет решение обязательности родителю.
     * 3) Иначе запускает строгую сборку переднего блока на отдельном курсоре по этим ячейкам.
     */
    private function buildOptionalFront(DetailsRowCursor $cursor): ?WiperFrontDetailsData
    {
        $cells = $this->pullCells($cursor, 6);

        if ($this->allBlank($cells)) {
            return null;
        }

        return $this->buildFront(new DetailsRowCursor($cells));
    }

    /**
     * Этот метод читает optional-задний блок дворников, не валидируя пустой набор как ошибку.
     * Шаги:
     * 1) Забирает из основного курсора ровно 4 ячейки заднего блока.
     * 2) Если все они пустые — возвращает null.
     * 3) Иначе запускает строгую сборку заднего блока на отдельном курсоре по этим ячейкам.
     */
    private function buildOptionalBack(DetailsRowCursor $cursor): ?WiperBackDetailsData
    {
        $cells = $this->pullCells($cursor, 4);

        if ($this->allBlank($cells)) {
            return null;
        }

        return $this->buildBack(new DetailsRowCursor($cells));
    }

    /**
     * Этот метод собирает передний блок дворников из 6 ячеек.
     * Шаги:
     * 1) Читает диапазон длины основной передней щётки.
     * 2) Читает диапазон длины второй передней щётки.
     * 3) Читает обязательный multi-select типов переднего крепления и переводит cases в names.
     * 4) Читает обязательное количество передних щёток.
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
     * Этот метод собирает задний блок дворников из 4 ячеек.
     * Шаги:
     * 1) Читает диапазон длины задней щётки.
     * 2) Читает обязательный multi-select типов заднего крепления и переводит cases в names.
     * 3) Читает обязательное количество задних щёток.
     */
    private function buildBack(DetailsRowCursor $cursor): WiperBackDetailsData
    {
        return new WiperBackDetailsData(
            lengthRear: $this->buildLengthRange($cursor),
            adapterTypeRear: $this->namesOf($cursor->pullRequiredMultiLabel(RearAdapterTypeEnum::class, 'Тип крепления задней')),
            countWipers: $cursor->pullRequiredIntCell('Количество задних щёток'),
        );
    }

    /**
     * Этот метод собирает диапазон длины щётки из двух integer-ячеек.
     * Шаги:
     * 1) Читает обязательную минимальную длину.
     * 2) Читает обязательную максимальную длину.
     * 3) Возвращает `WiperLengthRangeData`.
     */
    private function buildLengthRange(DetailsRowCursor $cursor): WiperLengthRangeData
    {
        return new WiperLengthRangeData(
            min: $cursor->pullRequiredIntCell('Минимальная длина щётки'),
            max: $cursor->pullRequiredIntCell('Максимальная длина щётки'),
        );
    }

    /**
     * Этот метод забирает фиксированное количество ячеек из курсора.
     * Шаги:
     * 1) Создаёт пустой список.
     * 2) `count` раз читает следующую ячейку через `pullCell()`.
     * 3) Возвращает список в исходном порядке чтения.
     *
     * @return array<int, string|int|float|null>
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
     * Этот метод проверяет, что optional-блок Excel-строки целиком пустой.
     * Шаги:
     * 1) Проходит по всем ячейкам блока.
     * 2) Любое значение, отличное от null и пустой строки, считает заполнением блока.
     * 3) Возвращает true только если заполненных ячеек не найдено.
     *
     * @param  array<int, string|int|float|null>  $cells
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
     * Шаги:
     * 1) Берёт у каждого enum-case его `name`.
     * 2) Сохраняет порядок значений из исходной multi-select ячейки.
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
