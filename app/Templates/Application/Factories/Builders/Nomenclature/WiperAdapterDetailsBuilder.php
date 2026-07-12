<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\Enums\PositionEnum;
use App\Templates\Domain\Enums\Wiper\ConstructionEnum;
use App\Templates\Domain\Enums\Wiper\FrontAdapterTypeEnum;
use App\Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData;

/**
 * Строит форму шаблона `wiperAdapter` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `WiperAdapterDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class WiperAdapterDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): WiperAdapterDetailsData
    {
        return new WiperAdapterDetailsData(
            position: $cursor->pullLabel(PositionEnum::class)?->name,
            construction: $cursor->pullLabel(ConstructionEnum::class)?->name,
            adapterTypeFront: array_map(
                static fn ($case) => $case->name,
                $cursor->pullMultiLabel(FrontAdapterTypeEnum::class),
            ),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
