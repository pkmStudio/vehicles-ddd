<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\TieRodEndDetailsData;

/**
 * Строит форму шаблона `tieRodEnd` (Nomenclature) из Excel-строки. Не подключена ни к одному
 * Import/Export сценарию — см. докблок `TieRodEndDetailsData`.
 */
final readonly class TieRodEndDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): TieRodEndDetailsData
    {
        return new TieRodEndDetailsData(
            thread1: $cursor->pullStringCell(),
            thread2: $cursor->pullStringCell(),
            length: $cursor->pullFloatCell(),
            coneSize: $cursor->pullFloatCell(),
            taper: $cursor->pullStringCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
