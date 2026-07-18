<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Services\Presenters\Nomenclature;

use App\Modules\Templates\Application\Traits\RendersNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

/** Рендерит форму `brakePads` (Nomenclature) в плоский набор Excel-ячеек экспорта. */
final readonly class BrakePadDetailsPresenter
{
    use RendersNomenclatureMetrics;

    public function headings(): array
    {
        return ['Расположение', 'Вид колодки', 'Материал накладок', ...$this->metricsHeadings()];
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
