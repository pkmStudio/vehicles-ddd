<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Templates\Domain\Enums\Filter\FormEnum;
use App\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;

/** Рендерит форму `airFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class AirFilterDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Исполнение фильтра', 'Форма фильтра', 'Корпус', 'Вид фильтра', ...$this->metricsHeadings()];
    }

    public function cells(AirFilterDetailsData $data): array
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
