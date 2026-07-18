<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Domain\ModelData\Nomenclature\NomenclatureMetricsData;

/** Общий блок `metrics` (length/width/height) — используется в 14 из 17 presenter-ов Nomenclature. */
trait RendersNomenclatureMetrics
{
    use FormatsExportCells;

    /** @return array<int, string> */
    private function metricsHeadings(): array
    {
        return ['Длина (мм)', 'Ширина (мм)', 'Высота (мм)'];
    }

    /** @return array<int, string> */
    private function metricsCells(NomenclatureMetricsData $metrics): array
    {
        return [
            $this->intArrayToString($metrics->length),
            $this->intArrayToString($metrics->width),
            $this->intArrayToString($metrics->height),
        ];
    }
}
