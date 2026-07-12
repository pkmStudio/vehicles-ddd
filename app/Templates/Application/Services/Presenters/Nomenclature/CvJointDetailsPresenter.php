<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\CvJointDetailsData;

/** Рендерит форму `cvJoint` (Nomenclature, ШРУС) в плоский набор Excel-ячеек экспорта. */
final readonly class CvJointDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return [
            'Резьба 1', 'Длина 1', 'Длина 2', 'ABS', 'Диаметр под сальник',
            'Шлицы наружные, шт.', 'Шлицы внутренние, шт.',
            ...$this->metricsHeadings(),
        ];
    }

    public function cells(CvJointDetailsData $data): array
    {
        return [
            $data->thread1,
            $data->length1,
            $data->length2,
            $data->abs,
            $data->sealDiameter,
            $data->splinesOuter,
            $data->splinesInner,
            ...$this->metricsCells($data->metrics),
        ];
    }
}
