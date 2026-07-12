<?php

declare(strict_types=1);

namespace App\Templates\Application\Services\Presenters\Nomenclature;

use App\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

/** Рендерит форму `brakePads` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class BrakePadDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Расположение', 'Тип тормозных колодок', 'Материал накладок', ...$this->metricsHeadings()];
    }

    public function cells(BrakePadDetailsData $data): array
    {
        return [
            $this->nameToLabelCell(PositionEnum::class, $data->position),
            $this->nameToLabelCell(BrakePadTypeEnum::class, $data->brakePadsType),
            $this->nameToLabelCell(LiningMaterialEnum::class, $data->materialLinings),
            ...$this->metricsCells($data->metrics),
        ];
    }
}
