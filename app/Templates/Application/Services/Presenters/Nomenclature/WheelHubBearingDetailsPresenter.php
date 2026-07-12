<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\WheelHubBearingDetailsData;

/** Рендерит форму `wheelHubBearing` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WheelHubBearingDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Высота', 'ABS', 'Крепление 1', 'Крепление 2', 'Внутренний диаметр', 'Внешний диаметр', ...$this->metricsHeadings()];
    }

    public function cells(WheelHubBearingDetailsData $data): array
    {
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
