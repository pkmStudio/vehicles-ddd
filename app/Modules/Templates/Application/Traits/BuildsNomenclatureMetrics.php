<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Traits;

use App\Modules\Templates\Application\Factories\DetailsRowCursor;
use App\Modules\Templates\Domain\ModelData\Nomenclature\NomenclatureMetricsData;

/** Общий блок `metrics` (length/width/height) — используется в 14 из 17 builder-ов Nomenclature. */
trait BuildsNomenclatureMetrics
{
    /**
     * Этот метод собирает общий metrics-блок номенклатурных builders.
     * Шаги:
     * 1) Читает обязательный список длин как integer values.
     * 2) Читает обязательный список ширин как integer values.
     * 3) Читает обязательный список высот как integer values.
     * 4) Возвращает `NomenclatureMetricsData`.
     */
    private function buildMetrics(DetailsRowCursor $cursor): NomenclatureMetricsData
    {
        return new NomenclatureMetricsData(
            length: $cursor->pullRequiredIntArray('Длина'),
            width: $cursor->pullRequiredIntArray('Ширина'),
            height: $cursor->pullRequiredIntArray('Высота'),
        );
    }
}
