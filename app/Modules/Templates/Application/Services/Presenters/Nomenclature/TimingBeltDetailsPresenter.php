<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TimingBeltDetailsData;

/** Рендерит форму `timingBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TimingBeltDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки ремня ГРМ.
     * Шаги:
     * 1) Добавляет диаметр диска сцепления.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Диаметр диска сцепления d1 (мм)', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а ремня ГРМ.
     * Шаги:
     * 1) Возвращает class-string `TimingBeltDetailsData`.
     *
     * @return class-string<TimingBeltDetailsData>
     */
    protected function dataClass(): string
    {
        return TimingBeltDetailsData::class;
    }

    /**
     * Этот метод рендерит details ремня ГРМ в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `TimingBeltDetailsData`.
     * 2) Выводит диаметр диска сцепления.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, TimingBeltDetailsData::class);

        return [
            $data->clutchDiscDiameter,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
