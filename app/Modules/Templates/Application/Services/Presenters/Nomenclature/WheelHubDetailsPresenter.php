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

    public function headings(): array
    {
        return [
            'Высота (мм)', 'ABS', 'Крепление 1', 'Крепление 2', 'Крепление 3',
            'Внутренний диаметр (мм)', 'Количество шлицев, шт.', 'Наружный диаметр (мм)',
            ...$this->metricsHeadings(),
        ];
    }

    /** @return class-string<WheelHubDetailsData> */
    protected function dataClass(): string
    {
        return WheelHubDetailsData::class;
    }

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
