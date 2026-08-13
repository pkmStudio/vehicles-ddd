<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodEndDetailsData;

/** Рендерит форму `tieRodEnd` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TieRodEndDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки наконечника рулевой тяги.
     * Шаги:
     * 1) Перечисляет резьбы, числовую длину, размер конуса и конусность.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', 'Размер конуса d (мм)', 'Конусность', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а наконечника рулевой тяги.
     * Шаги:
     * 1) Возвращает class-string `TieRodEndDetailsData`.
     *
     * @return class-string<TieRodEndDetailsData>
     */
    protected function dataClass(): string
    {
        return TieRodEndDetailsData::class;
    }

    /**
     * Этот метод рендерит details наконечника рулевой тяги в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `TieRodEndDetailsData`.
     * 2) Выводит scalar-поля без enum-преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, TieRodEndDetailsData::class);

        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->coneSize,
            $data->taper,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
