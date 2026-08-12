<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TimingBeltDetailsData;

/** Рендерит форму `timingBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TimingBeltDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Диаметр диска сцепления d1 (мм)', ...$this->metricsHeadings()];
    }

    /** @return class-string<TimingBeltDetailsData> */
    protected function dataClass(): string
    {
        return TimingBeltDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, TimingBeltDetailsData::class);

        return [
            $data->clutchDiscDiameter,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
