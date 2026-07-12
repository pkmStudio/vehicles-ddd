<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\StabilizerLinkDetailsData;

/**
 * Строит форму шаблона `stabilizerLink` (Nomenclature) из Excel-строки. Не подключена ни к
 * одному Import/Export сценарию — см. докблок `StabilizerLinkDetailsData`.
 */
final readonly class StabilizerLinkDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): StabilizerLinkDetailsData
    {
        return new StabilizerLinkDetailsData(
            thread1: $cursor->pullStringCell(),
            thread2: $cursor->pullStringCell(),
            length: $cursor->pullFloatCell(),
            metrics: $this->buildMetrics($cursor),
        );
    }
}
