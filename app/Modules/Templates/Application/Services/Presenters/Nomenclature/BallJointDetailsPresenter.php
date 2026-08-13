<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BallJointDetailsData;

/** Рендерит форму `ballJoint` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class BallJointDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки шаровой опоры.
     * Шаги:
     * 1) Перечисляет резьбы, длину, внешний диаметр, размер конуса и конусность.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return [
            'Резьба 1',
            'Резьба 2',
            'Длина (мм)',
            'Наружный диаметр (мм)',
            'Размер конуса d (мм)',
            'Конусность',
            ...$this->metricsHeadings(),
        ];
    }

    /**
     * Этот метод указывает Data-класс presenter-а шаровой опоры.
     * Шаги:
     * 1) Возвращает class-string `BallJointDetailsData`.
     *
     * @return class-string<BallJointDetailsData>
     */
    protected function dataClass(): string
    {
        return BallJointDetailsData::class;
    }

    /**
     * Этот метод рендерит details шаровой опоры в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `BallJointDetailsData`.
     * 2) Выводит scalar-поля без enum-преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, BallJointDetailsData::class);

        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->outerDiameter,
            $data->coneSize,
            $data->taper,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
