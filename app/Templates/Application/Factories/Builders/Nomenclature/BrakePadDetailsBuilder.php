<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

/**
 * Строит форму шаблона `brakePads` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `BrakePadDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class BrakePadDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): BrakePadDetailsData
    {
        return new BrakePadDetailsData(
            position: $cursor->pullLabel(PositionEnum::class)?->name,
            brakePadsType: $cursor->pullLabel(BrakePadTypeEnum::class)?->name,
            materialLinings: $cursor->pullLabel(LiningMaterialEnum::class)?->name,
            metrics: $this->buildMetrics($cursor),
        );
    }
}
