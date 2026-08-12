<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\TieRod\ApplicationEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodDetailsData;

/** Рендерит форму `tieRod` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class TieRodDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Резьба 1', 'Резьба 2', 'Длина (мм)', 'Размер конуса d (мм)', 'Конусность', 'Применение', ...$this->metricsHeadings()];
    }

    /** @return class-string<TieRodDetailsData> */
    protected function dataClass(): string
    {
        return TieRodDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, TieRodDetailsData::class);

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
