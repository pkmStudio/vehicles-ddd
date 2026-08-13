<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

/** Рендерит форму `brakePads` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class BrakePadDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки тормозных колодок.
     * Шаги:
     * 1) Перечисляет расположение, вид колодки и материал накладок.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Расположение', 'Вид колодки', 'Материал накладок', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а тормозных колодок.
     * Шаги:
     * 1) Возвращает class-string `BrakePadDetailsData`.
     *
     * @return class-string<BrakePadDetailsData>
     */
    protected function dataClass(): string
    {
        return BrakePadDetailsData::class;
    }

    /**
     * Этот метод рендерит details тормозных колодок в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `BrakePadDetailsData`.
     * 2) Переводит enum-name поля расположения, вида и материала в labels.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, BrakePadDetailsData::class);

        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->nameToLabelCell(BrakePadTypeEnum::class, $data->brakePadsType),
            $this->nameToLabelCell(LiningMaterialEnum::class, $data->materialLinings),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
