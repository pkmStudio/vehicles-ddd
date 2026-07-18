<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;

/** Рендерит форму `stabilizerLink` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class StabilizerLinkDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', ...$this->metricsHeadings()];
    }

    public function cells(StabilizerLinkDetailsData $data): array
    {
        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
