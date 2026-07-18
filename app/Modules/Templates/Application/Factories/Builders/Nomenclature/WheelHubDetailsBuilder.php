<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WheelHubDetailsData;

/**
 * Строит форму шаблона `wheelHub` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `WheelHubDetailsData`. `height`/`outerDiameter` — числа
 * (в отличие от `WheelHubBearingDetailsBuilder`, см. докблок Data-класса).
 */
final readonly class WheelHubDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): WheelHubDetailsData
    {
        return new WheelHubDetailsData(
            height: $cursor->pullFloatCell(),
            abs: $cursor->pullStringCell(),
            mount1: $cursor->pullStringCell(),
            mount2: $cursor->pullStringCell(),
            mount3: $cursor->pullStringCell(),
            innerDiameter: $cursor->pullStringCell(),
            splinesCount: $cursor->pullIntCell(),
            outerDiameter: $cursor->pullFloatCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
