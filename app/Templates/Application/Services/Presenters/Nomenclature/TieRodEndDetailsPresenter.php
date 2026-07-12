<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\TieRodEndDetailsData;

/** Рендерит форму `tieRodEnd` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TieRodEndDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', 'Размер конуса d (мм)', 'Конусность', ...$this->metricsHeadings()];
    }

    public function cells(TieRodEndDetailsData $data): array
    {
        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->coneSize,
            $data->taper,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
