<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Services\Presenters\AbstractDetailsPresenter;
use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;

/** Рендерит форму `wiperAdapter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WiperAdapterDetailsPresenter extends AbstractDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Расположение', 'Тип крепления передних', ...$this->metricsHeadings()];
    }

    /** @return class-string<WiperAdapterDetailsData> */
    protected function dataClass(): string
    {
        return WiperAdapterDetailsData::class;
    }

    public function cells(AbstractDetailsData $data): array
    {
        $data = $this->ensureData($data, WiperAdapterDetailsData::class);

        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->namesToLabelString($data->adapterTypeFront, FrontAdapterTypeEnum::class),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
