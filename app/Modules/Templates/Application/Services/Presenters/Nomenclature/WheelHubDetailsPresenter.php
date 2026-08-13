<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubDetailsData;

/** Рендерит форму `wheelHub` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WheelHubDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки ступицы.
     * Шаги:
     * 1) Перечисляет высоту, ABS, крепления, внутренний диаметр, шлицы и внешний диаметр.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return [
            'Высота (мм)', 'ABS', 'Крепление 1', 'Крепление 2', 'Крепление 3',
            'Внутренний диаметр (мм)', 'Количество шлицев, шт.', 'Наружный диаметр (мм)',
            ...$this->metricsHeadings(),
        ];
    }

    /**
     * Этот метод указывает Data-класс presenter-а ступицы.
     * Шаги:
     * 1) Возвращает class-string `WheelHubDetailsData`.
     *
     * @return class-string<WheelHubDetailsData>
     */
    protected function dataClass(): string
    {
        return WheelHubDetailsData::class;
    }

    /**
     * Этот метод рендерит details ступицы в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `WheelHubDetailsData`.
     * 2) Выводит scalar-поля без enum-преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WheelHubDetailsData::class);

        return [
            $data->height,
            $data->abs,
            $data->mount1,
            $data->mount2,
            $data->mount3,
            $data->innerDiameter,
            $data->splinesCount,
            $data->outerDiameter,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
