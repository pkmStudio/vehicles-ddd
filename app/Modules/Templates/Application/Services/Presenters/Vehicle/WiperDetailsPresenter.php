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

    public function headings(): array
    {
        return [...$this->frontHeadings(), ...$this->backHeadings()];
    }

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
     * Этот метод рендерит значения дворников (обе стороны) как плоский набор Excel-ячеек.
     * Шаги:
     * 1) Берёт переднюю сторону, если она есть; если её нет (в смерженном
     *    `WiperSpecificationService::mergeForExport()` массиве не было ключа `front`) —
     *    возвращает пустые ячейки, чтобы количество колонок не поменялось.
     * 2) То же самое для задней стороны.
     * 3) Разворачивает ячейки обеих сторон подряд в один плоский список.
     */
    /** @return class-string<WiperDetailsData> */
    protected function dataClass(): string
    {
        return WiperDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WiperDetailsData::class);

        return [
            ...$this->frontCells($data->front),
            ...$this->backCells($data->back),
        ];
    }

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

    /** @return array{0: ?int, 1: ?int} */
    private function lengthRangeCells(WiperLengthRangeData $range): array
    {
        return [$range->min, $range->max];
    }
}
