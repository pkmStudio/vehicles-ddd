<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\AirFilterDetailsData;

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
