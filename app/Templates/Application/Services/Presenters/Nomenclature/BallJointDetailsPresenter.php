<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\BallJointDetailsData;

/** Рендерит форму `ballJoint` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class BallJointDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return [
            'Резьба 1',
            'Резьба 2',
            'Длина (мм)',
            'Наружный диаметр (мм)',
            'Размер конуса d (мм)',
            'Конусность',
            ...$this->metricsHeadings(),
        ];
    }

    public function cells(BallJointDetailsData $data): array
    {
        return [
            $data->thread1,
            $data->thread2,
            $data->length,
            $data->outerDiameter,
            $data->coneSize,
            $data->taper,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
