<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\TimingBeltDetailsData;

/** Рендерит форму `timingBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TimingBeltDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Диаметр диска сцепления d1, мм', ...$this->metricsHeadings()];
    }

    public function cells(TimingBeltDetailsData $data): array
    {
        return [
            $data->clutchDiscDiameter,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
