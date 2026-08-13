<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Vehicle;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\FormatsExportCells;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\Enums\Wiper\RearAdapterTypeEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperBackDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperFrontDetailsData;
use App\Modules\Templates\Domain\ModelData\Vehicle\WiperLengthRangeData;

/**
 * Рендерит форму `wiper` (обе стороны) в плоский набор Excel-ячеек экспорта. Выделено из
 * `DetailsDataPresenter`. Простой класс без собственного порта — вызывается только оттуда.
 */
final readonly class WiperDetailsPresenter extends AbstractDetailsPresenter
{
    use FormatsExportCells;

    /**
     * Этот метод возвращает колонки vehicle wiper шаблона.
     * Шаги:
     * 1) Добавляет заголовки передней стороны.
     * 2) Добавляет заголовки задней стороны.
     * 3) Сохраняет порядок, ожидаемый `cells()`.
     */
    public function headings(): array
    {
        return [...$this->frontHeadings(), ...$this->backHeadings()];
    }

    /**
     * Этот метод возвращает заголовки передней стороны дворников.
     * Шаги:
     * 1) Перечисляет диапазоны водительской и пассажирской щётки.
     * 2) Добавляет тип крепления передних и количество передних щёток.
     */
    private function frontHeadings(): array
    {
        return [
            'Размеры водительской щетки в мм От',
            'Размеры водительской щетки в мм До',
            'Размеры пассажирской щетки в мм От',
            'Размеры пассажирской щетки в мм До',
            'Тип крепления передних',
            'Количество передних щеток',
        ];
    }

    /**
     * Этот метод возвращает заголовки задней стороны дворников.
     * Шаги:
     * 1) Перечисляет диапазон задней щётки.
     * 2) Добавляет тип крепления задней и количество задних щёток.
     */
    private function backHeadings(): array
    {
        return [
            'Размеры задней щетки в мм От',
            'Размеры задней щетки в мм До',
            'Тип крепления задней',
            'Количество задних щеток',
        ];
    }

    /**
     * Этот метод указывает Data-класс vehicle wiper presenter-а.
     * Шаги:
     * 1) Возвращает class-string `WiperDetailsData` для восстановления details массива.
     *
     * @return class-string<WiperDetailsData>
     */
    protected function dataClass(): string
    {
        return WiperDetailsData::class;
    }

    /**
     * Этот метод рендерит значения дворников (обе стороны) как плоский набор Excel-ячеек.
     * Шаги:
     * 1) Проверяет, что получен `WiperDetailsData`.
     * 2) Рендерит переднюю сторону; при отсутствии данных возвращает пустые placeholders.
     * 3) Рендерит заднюю сторону; при отсутствии данных возвращает пустые placeholders.
     * 4) Разворачивает ячейки обеих сторон подряд в один плоский список.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WiperDetailsData::class);

        return [
            ...$this->frontCells($data->front),
            ...$this->backCells($data->back),
        ];
    }

    /**
     * Этот метод рендерит переднюю сторону дворников.
     * Шаги:
     * 1) Если передней стороны нет — возвращает пустой набор из 6 ячеек для стабильной ширины
     *    Excel-строки.
     * 2) Иначе разворачивает два диапазона длины.
     * 3) Склеивает типы переднего крепления в `;`-строку labels и добавляет количество щёток.
     */
    private function frontCells(?WiperFrontDetailsData $front): array
    {
        if ($front === null) {
            return [null, null, null, null, '', null];
        }

        return [
            ...$this->lengthRangeCells($front->lengthMain),
            ...$this->lengthRangeCells($front->lengthSecond),
            $this->namesToLabelString($front->adapterTypeFront, FrontAdapterTypeEnum::class),
            $front->countWipers,
        ];
    }

    /**
     * Этот метод рендерит заднюю сторону дворников.
     * Шаги:
     * 1) Если задней стороны нет — возвращает пустой набор из 4 ячеек.
     * 2) Иначе разворачивает диапазон длины задней щётки.
     * 3) Склеивает типы заднего крепления в labels и добавляет количество щёток.
     */
    private function backCells(?WiperBackDetailsData $back): array
    {
        if ($back === null) {
            return [null, null, '', null];
        }

        return [
            ...$this->lengthRangeCells($back->lengthRear),
            $this->namesToLabelString($back->adapterTypeRear, RearAdapterTypeEnum::class),
            $back->countWipers,
        ];
    }

    /**
     * Этот метод превращает диапазон длины щётки в две Excel-ячейки.
     * Шаги:
     * 1) Берёт минимальную длину.
     * 2) Берёт максимальную длину.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function lengthRangeCells(WiperLengthRangeData $range): array
    {
        return [$range->min, $range->max];
    }
}
