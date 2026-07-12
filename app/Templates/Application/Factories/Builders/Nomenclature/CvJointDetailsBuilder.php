<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\CvJointDetailsData;

/**
 * Строит форму шаблона `cvJoint` (Nomenclature, ШРУС) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `CvJointDetailsData`.
 */
final readonly class CvJointDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): CvJointDetailsData
    {
        return new CvJointDetailsData(
            thread1: $cursor->pullStringCell(),
            length1: $cursor->pullFloatCell(),
            length2: $cursor->pullFloatCell(),
            abs: $cursor->pullStringCell(),
            sealDiameter: $cursor->pullFloatCell(),
            splinesOuter: $cursor->pullIntCell(),
            splinesInner: $cursor->pullIntCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
