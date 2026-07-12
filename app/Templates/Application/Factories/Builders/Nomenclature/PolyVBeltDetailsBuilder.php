<?php

declare(strict_types=1);

namespace App\Templates\Application\Factories\Builders\Nomenclature;

use App\Templates\Application\Factories\DetailsRowCursor;
use App\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;

/**
 * Строит форму шаблона `polyVBelt` (Nomenclature) из Excel-строки — только базовые габариты.
 * Не подключена ни к одному Import/Export сценарию — см. докблок `PolyVBeltDetailsData`.
 */
final readonly class PolyVBeltDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    public function build(DetailsRowCursor $cursor): PolyVBeltDetailsData
    {
        return new PolyVBeltDetailsData(
            metrics: $this->buildMetrics($cursor),
        );
    }
}
