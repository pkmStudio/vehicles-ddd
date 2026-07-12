<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\BallJointDetailsData;

/**
 * Строит форму шаблона `ballJoint` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `BallJointDetailsData`.
 */
final readonly class BallJointDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): BallJointDetailsData
    {
        return new BallJointDetailsData(
            thread1: $cursor->pullStringCell(),
            thread2: $cursor->pullStringCell(),
            length: $cursor->pullFloatCell(),
            outerDiameter: $cursor->pullStringCell(),
            coneSize: $cursor->pullFloatCell(),
            taper: $cursor->pullStringCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
