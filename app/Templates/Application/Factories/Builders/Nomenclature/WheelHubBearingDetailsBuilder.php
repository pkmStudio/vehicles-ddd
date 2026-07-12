<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\WheelHubBearingDetailsData;

/**
 * Строит форму шаблона `wheelHubBearing` (Nomenclature) из Excel-строки. Не подключена ни к
 * одному Import/Export сценарию — см. докблок `WheelHubBearingDetailsData`. `height` — строка
 * (не число), см. докблок Data-класса.
 */
final readonly class WheelHubBearingDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): WheelHubBearingDetailsData
    {
        return new WheelHubBearingDetailsData(
            height: $cursor->pullStringCell(),
            abs: $cursor->pullStringCell(),
            mount1: $cursor->pullStringCell(),
            mount2: $cursor->pullStringCell(),
            innerDiameter: $cursor->pullStringCell(),
            outerDiameter: $cursor->pullStringCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
