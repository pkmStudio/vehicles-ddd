<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Factories\Builders\Nomenclature;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Application\Traits\BuildsNomenclatureMetrics;
use App\Modules\Templates\Domain\ModelData\Nomenclature\PolyVBeltDetailsData;

/**
 * Строит форму шаблона `polyVBelt` (Nomenclature) из Excel-строки — только базовые габариты.
 * Не подключена ни к одному Import/Export сценарию — см. докблок `PolyVBeltDetailsData`.
 */
final readonly class PolyVBeltDetailsBuilder
{
    use BuildsNomenclatureMetrics;

    /**
     * Этот метод собирает details поликлинового ремня из Excel-строки.
     * Шаги:
     * 1) Читает только общий блок габаритов номенклатуры.
     * 2) Возвращает `PolyVBeltDetailsData`.
     */
    public function build(DetailsRowCursor $cursor): PolyVBeltDetailsData
    {
        return new PolyVBeltDetailsData(
            metrics: $this->buildMetrics($cursor),
        );
    }
}
