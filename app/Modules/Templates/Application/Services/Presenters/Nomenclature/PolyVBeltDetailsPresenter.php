<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;

/** Рендерит форму `polyVBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта — только базовые габариты. */
final readonly class PolyVBeltDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return $this->metricsHeadings();
    }

    /** @return class-string<PolyVBeltDetailsData> */
    protected function dataClass(): string
    {
        return PolyVBeltDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, PolyVBeltDetailsData::class);

        return $this->metricsCells($data->metrics);
    }
}
