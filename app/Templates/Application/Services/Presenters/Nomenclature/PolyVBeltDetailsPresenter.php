<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;

/** Рендерит форму `polyVBelt` (Nomenclature) в плоский набор Excel-ячеек экспорта — только базовые габариты. */
final readonly class PolyVBeltDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return $this->metricsHeadings();
    }

    public function cells(PolyVBeltDetailsData $data): array
    {
        return $this->metricsCells($data->metrics);
    }
}
