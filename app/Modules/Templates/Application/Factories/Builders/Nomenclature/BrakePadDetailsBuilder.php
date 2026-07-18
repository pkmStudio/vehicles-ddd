<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\Enums\BrakePad\BrakePadTypeEnum;
use App\Modules\Templates\Domain\Enums\BrakePad\LiningMaterialEnum;
use App\Modules\Templates\Domain\Enums\PositionEnum;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BrakePadDetailsData;

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
