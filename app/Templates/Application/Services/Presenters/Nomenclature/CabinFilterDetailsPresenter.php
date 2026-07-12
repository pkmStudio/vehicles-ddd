<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Templates\Domain\Enums\Filter\FormEnum;
use App\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Templates\Domain\ModelData\Nomenclature\CabinFilterDetailsData;

/** Рендерит форму `cabinFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта — структурно идентична `AirFilterDetailsPresenter`. */
final readonly class CabinFilterDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Исполнение', 'Форма', 'Корпус', 'Тип фильтрующего элемента', ...$this->metricsHeadings()];
    }

    public function cells(CabinFilterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->boolToLabelCell($data->frame),
            $this->nameToLabelCell(FilterMediaTypeEnum::class, $data->filterType),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
