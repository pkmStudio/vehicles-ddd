<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\TieRod\ApplicationEnum;
use App\Templates\Domain\ModelData\Nomenclature\TieRodDetailsData;

/** Рендерит форму `tieRod` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TieRodDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина', 'Размер конуса d', 'Конусность', 'Назначение', ...$this->metricsHeadings()];
    }

    public function cells(TieRodDetailsData $data): array
    {
        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->coneSize,
            $data->taper,
            $this->nameToLabelCell(ApplicationEnum::class, $data->application),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
