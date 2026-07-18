<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\ModelData\Nomenclature\NomenclatureMetricsData;

/** Общий блок `metrics` (length/width/height) — используется в 14 из 17 builder-ов Nomenclature. */
trait BuildsNomenclatureMetrics
{
    private function buildMetrics(DetailsRowCursor $cursor): NomenclatureMetricsData
    {
        return new NomenclatureMetricsData(
            length: $cursor->pullIntArray(),
            width: $cursor->pullIntArray(),
            height: $cursor->pullIntArray(),
        );
    }
}
