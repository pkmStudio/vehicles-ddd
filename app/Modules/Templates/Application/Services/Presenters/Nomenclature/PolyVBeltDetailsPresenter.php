<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;

/** Рендерит форму `polyVBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта — только базовые габариты. */
final readonly class PolyVBeltDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки поликлинового ремня.
     * Шаги:
     * 1) Возвращает только общий metrics-блок, потому что других details-полей нет.
     */
    public function headings(): array
    {
        return $this->metricsHeadings();
    }

    /**
     * Этот метод указывает Data-класс presenter-а поликлинового ремня.
     * Шаги:
     * 1) Возвращает class-string `PolyVBeltDetailsData`.
     *
     * @return class-string<PolyVBeltDetailsData>
     */
    protected function dataClass(): string
    {
        return PolyVBeltDetailsData::class;
    }

    /**
     * Этот метод рендерит details поликлинового ремня в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `PolyVBeltDetailsData`.
     * 2) Возвращает ячейки общего metrics-блока.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, PolyVBeltDetailsData::class);

        return $this->metricsCells($data->metrics);
    }
}
