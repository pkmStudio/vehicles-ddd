<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Domain\ModelData\Nomenclature\NomenclatureMetricsData;

/** Общий блок `metrics` (length/width/height) — используется в 14 из 17 presenter-ов Nomenclature. */
trait RendersNomenclatureMetrics
{
    use FormatsExportCells;

    /**
     * Этот метод возвращает заголовки общего metrics-блока номенклатуры.
     * Шаги:
     * 1) Перечисляет длину, ширину и высоту в порядке `metricsCells()`.
     *
     * @return array<int, string>
     */
    private function metricsHeadings(): array
    {
        return ['Длина (мм)', 'Ширина (мм)', 'Высота (мм)'];
    }

    /**
     * Этот метод рендерит общий metrics-блок номенклатуры.
     * Шаги:
     * 1) Склеивает integer-списки длины, ширины и высоты через `;`.
     * 2) Возвращает три ячейки в порядке заголовков.
     *
     * @return array<int, string>
     */
    private function metricsCells(NomenclatureMetricsData $metrics): array
    {
        return [
            $this->intArrayToString($metrics->length),
            $this->intArrayToString($metrics->width),
            $this->intArrayToString($metrics->height),
        ];
    }
}
