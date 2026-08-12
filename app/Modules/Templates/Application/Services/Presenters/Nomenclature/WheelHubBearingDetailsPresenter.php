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

    /** @return class-string<WheelHubBearingDetailsData> */
    protected function dataClass(): string
    {
        return WheelHubBearingDetailsData::class;
    }

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
