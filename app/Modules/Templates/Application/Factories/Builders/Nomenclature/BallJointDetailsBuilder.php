<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\BallJointDetailsData;

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
            thread1: $cursor->pullRequiredStringCell('Резьба 1'),
            thread2: $cursor->pullRequiredStringCell('Резьба 2'),
            length: $cursor->pullRequiredFloatCell('Длина'),
            outerDiameter: $cursor->pullRequiredStringCell('Внешний диаметр'),
            coneSize: $cursor->pullRequiredFloatCell('Размер конуса'),
            taper: $cursor->pullRequiredStringCell('Конусность'),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
