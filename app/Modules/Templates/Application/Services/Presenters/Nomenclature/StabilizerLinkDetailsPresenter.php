<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;

/** Рендерит форму `stabilizerLink` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class StabilizerLinkDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', ...$this->metricsHeadings()];
    }

    /** @return class-string<StabilizerLinkDetailsData> */
    protected function dataClass(): string
    {
        return StabilizerLinkDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, StabilizerLinkDetailsData::class);

        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
