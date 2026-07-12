<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;

/** Рендерит форму `wiperAdapter` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class WiperAdapterDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Расположение', 'Конструкция', 'Тип крепления передних', ...$this->metricsHeadings()];
    }

    public function cells(WiperAdapterDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->nameToLabelCell(ConstructionEnum::class, $data->construction),
            $this->namesToLabelString($data->adapterTypeFront, FrontAdapterTypeEnum::class),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
