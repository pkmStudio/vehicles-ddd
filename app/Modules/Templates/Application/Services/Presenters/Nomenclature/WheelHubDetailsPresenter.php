<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubDetailsData;

/** Рендерит форму `wheelHub` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WheelHubDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return [
            'Высота (мм)', 'ABS', 'Крепление 1', 'Крепление 2', 'Крепление 3',
            'Внутренний диаметр (мм)', 'Количество шлицев, шт.', 'Наружный диаметр (мм)',
            ...$this->metricsHeadings(),
        ];
    }

    public function cells(WheelHubDetailsData $data): array
    {
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
