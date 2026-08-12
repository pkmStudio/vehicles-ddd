<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;

/** Рендерит форму `stabilizerLink` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class StabilizerLinkDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки стойки стабилизатора.
     * Шаги:
     * 1) Перечисляет две резьбы и длину.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а стойки стабилизатора.
     * Шаги:
     * 1) Возвращает class-string `StabilizerLinkDetailsData`.
     *
     * @return class-string<StabilizerLinkDetailsData>
     */
    protected function dataClass(): string
    {
        return StabilizerLinkDetailsData::class;
    }

    /**
     * Этот метод рендерит details стойки стабилизатора в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `StabilizerLinkDetailsData`.
     * 2) Выводит резьбы и длину без enum-преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, StabilizerLinkDetailsData::class);

        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
