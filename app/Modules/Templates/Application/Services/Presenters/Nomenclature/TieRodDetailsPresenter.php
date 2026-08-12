<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\TieRod\ApplicationEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodDetailsData;

/** Рендерит форму `tieRod` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TieRodDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки рулевой тяги.
     * Шаги:
     * 1) Перечисляет резьбы, длину, размер конуса, конусность и применение.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', 'Размер конуса d (мм)', 'Конусность', 'Применение', ...$this->metricsHeadings()];
    }

    /**
     * Этот метод указывает Data-класс presenter-а рулевой тяги.
     * Шаги:
     * 1) Возвращает class-string `TieRodDetailsData`.
     *
     * @return class-string<TieRodDetailsData>
     */
    protected function dataClass(): string
    {
        return TieRodDetailsData::class;
    }

    /**
     * Этот метод рендерит details рулевой тяги в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `TieRodDetailsData`.
     * 2) Выводит scalar-поля и переводит применение из enum-name в label.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, TieRodDetailsData::class);

        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->coneSize,
            $data->taper,
            $this->nameToLabelCell(ApplicationEnum::class, $data->application),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
