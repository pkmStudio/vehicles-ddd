<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TimingBeltDetailsData;

/**
 * Строит форму шаблона `timingBelt` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `TimingBeltDetailsData`. Простой класс без собственного
 * порта — вызывается только из `NomenclatureDetailsDataFactory`.
 */
final readonly class TimingBeltDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): TimingBeltDetailsData
    {
        return new TimingBeltDetailsData(
            clutchDiscDiameter: $cursor->pullFloatCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
