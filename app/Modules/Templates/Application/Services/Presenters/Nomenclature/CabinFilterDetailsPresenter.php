<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\Filter\FilterMediaTypeEnum;
use App\Modules\Templates\Domain\Enums\Filter\FormEnum;
use App\Modules\Templates\Domain\Enums\Filter\PerformanceEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\CabinFilterDetailsData;

/** Рендерит форму `cabinFilter` (Nomenclature) в плоский набор Excel-ячеек экспорта — структурно идентична `AirFilterDetailsPresenter`. */
final readonly class CabinFilterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Исполнение фильтра', 'Форма фильтра', 'Корпус', 'Вид фильтра', ...$this->metricsHeadings()];
    }

    /** @return class-string<CabinFilterDetailsData> */
    protected function dataClass(): string
    {
        return CabinFilterDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, CabinFilterDetailsData::class);

        return [
            $this->nameToLabelCell(PerformanceEnum::class, $data->performance),
            $this->nameToLabelCell(FormEnum::class, $data->form),
            $this->boolToLabelCell($data->frame),
            $this->nameToLabelCell(FilterMediaTypeEnum::class, $data->filterType),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
