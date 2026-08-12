<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubBearingDetailsData;

/** Рендерит форму `wheelHubBearing` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WheelHubBearingDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    /**
     * Этот метод возвращает колонки ступичного подшипника.
     * Шаги:
     * 1) Перечисляет высоту, ABS, два крепления и диаметры.
     * 2) Добавляет общий metrics-блок.
     */
    public function headings(): array
    {
        return [
            'Высота (мм)',
            'ABS',
            'Крепление 1',
            'Крепление 2',
            'Внутренний диаметр (мм)',
            'Наружный диаметр (мм)',
            ...$this->metricsHeadings(),
        ];
    }

    /**
     * Этот метод указывает Data-класс presenter-а ступичного подшипника.
     * Шаги:
     * 1) Возвращает class-string `WheelHubBearingDetailsData`.
     *
     * @return class-string<WheelHubBearingDetailsData>
     */
    protected function dataClass(): string
    {
        return WheelHubBearingDetailsData::class;
    }

    /**
     * Этот метод рендерит details ступичного подшипника в Excel-ячейки.
     * Шаги:
     * 1) Проверяет тип `WheelHubBearingDetailsData`.
     * 2) Выводит scalar-поля без enum-преобразований.
     * 3) Разворачивает metrics-блок.
     */
    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WheelHubBearingDetailsData::class);

        return [
            $data->height,
            $data->abs,
            $data->mount1,
            $data->mount2,
            $data->innerDiameter,
            $data->outerDiameter,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
